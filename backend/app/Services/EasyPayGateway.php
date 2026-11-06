<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class EasyPayGateway
{
    private const HOST = 'https://www.e-com-easypay.com/';

    public function credentials(int $restaurant): array
    {
        abort_unless(config('easypay.enabled'), 503, 'Paiement EasyPay non configuré.');
        $credentials = config('easypay.merchants.'.$restaurant);
        abort_unless(is_array($credentials) && ! empty($credentials['cid']) && ! empty($credentials['publishable_key']), 503, 'Compte marchand du restaurant non configuré.');
        $mode = config('easypay.mode');
        abort_unless(in_array($mode, ['sandbox', 'v1'], true), 503, 'Mode EasyPay invalide.');
        abort_if(app()->isProduction() && $mode !== 'v1', 503, 'Le mode de test est interdit en production.');
        abort_if(app()->isProduction() && ! str_starts_with(config('easypay.return_base'), 'https://'), 503, 'Le retour de paiement doit utiliser HTTPS en production.');

        return $credentials + ['mode' => $mode];
    }

    public function publicStatus(int $restaurant): array
    {
        try {
            $credentials = $this->credentials($restaurant);
        } catch (HttpException) {
            return ['available' => false, 'test_mode' => false, 'networks' => []];
        }
        // Publier uniquement les réseaux confirmés pour ce marchand, jamais les clés.
        $confirmed = $credentials['confirmed_networks'] ?? [];
        $networks = is_array($confirmed)
            ? array_values(array_intersect(['orange_money', 'mpesa', 'airtel_money'], array_filter($confirmed, 'is_string')))
            : [];

        return ['available' => true, 'test_mode' => $credentials['mode'] === 'sandbox', 'networks' => $networks];
    }

    public function initialize(Payment $payment, string $name): string
    {
        $credentials = $this->credentials($payment->restaurant_id);
        $query = http_build_query(['cid' => $credentials['cid'], 'token' => $credentials['publishable_key']]);
        $return = rtrim(config('easypay.return_base'), '/').'/payment-return';
        $payload = [
            'order_ref' => $payment->merchant_order_ref,
            'currency' => 'CDF',
            'customer_name' => $name,
            'description' => 'Commande Mon Miam Miam',
            'language' => 'FR',
            'channels' => [['channel' => 'CREDIT CARD'], ['channel' => 'MOBILE MONEY']],
            'success_url' => $return.'?result=success',
            'cancel_url' => $return.'?result=cancel',
            'error_url' => $return.'?result=error',
        ];
        // Émettre un nombre JSON exact, sans convertir le décimal monétaire en float.
        $body = substr(json_encode($payload, JSON_THROW_ON_ERROR), 0, -1).',"amount":'.$payment->amount.'}';
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->withBody($body, 'application/json')->post(self::HOST.$payment->mode.'/payment/initialization?'.$query);
        } catch (ConnectionException) {
            // Ne pas laisser une exception HTTP journaliser l'URL qui contient les identifiants.
            abort(502, 'Initialisation incertaine. Ne relancez pas un encaissement sans rapprochement.');
        }
        $data = $response->json();
        abort_unless($response->successful() && is_array($data) && (int) ($data['code'] ?? 0) === 1, 502, 'Initialisation non confirmée par EasyPay.');
        $reference = $data['reference'] ?? '';
        abort_unless(is_string($reference) && preg_match('/^[A-Za-z0-9=_-]{1,150}$/', $reference), 502, 'Référence EasyPay invalide.');

        return $reference;
    }

    public function initializeMobileMoney(Payment $payment, string $name, string $phone): string
    {
        $credentials = $this->credentials($payment->restaurant_id);
        $query = http_build_query(['cid' => $credentials['cid'], 'token' => $credentials['publishable_key']]);
        // API directe documentée : numéro national sans zéro initial ni indicatif.
        $payload = [
            'order_ref' => $payment->merchant_order_ref,
            'currency' => 'CDF',
            'customer_phone' => $phone,
            'customer_name' => $name,
            'description' => 'Commande Mon Miam Miam',
        ];
        $body = substr(json_encode($payload, JSON_THROW_ON_ERROR), 0, -1).',"amount":'.$payment->amount.'}';
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->withBody($body, 'application/json')->post(self::HOST.$payment->mode.'/mobile-money/payment?'.$query);
        } catch (ConnectionException) {
            abort(502, 'EasyPay ne confirme pas la demande Mobile Money. Ne la répétez pas avant rapprochement.');
        }
        $data = $response->json();
        abort_unless($response->successful() && is_array($data), 502, 'Le service Mobile Money EasyPay est indisponible. Aucun paiement confirmé.');
        if (in_array($data['code'] ?? null, [0, '0'], true)) {
            $message = is_string($data['message'] ?? null) ? $data['message'] : 'Demande refusée sans motif exploitable.';
            $message = str_replace([$credentials['cid'], $credentials['publishable_key'], $phone], '[masqué]', $message);
            abort(422, 'EasyPay : '.mb_substr(strip_tags($message), 0, 300));
        }
        $reference = $data['reference'] ?? null;
        abort_unless((int) ($data['code'] ?? 0) === 1 && is_string($reference)
            && preg_match('/^[A-Za-z0-9=_-]{1,150}$/', $reference), 502, 'Demande Mobile Money non confirmée par EasyPay. Ne relancez pas sans rapprochement.');

        return $reference;
    }

    public function check(Payment $payment): array
    {
        $credentials = $this->credentials($payment->restaurant_id);
        abort_unless(hash('sha256', $credentials['cid']) === $payment->merchant_fingerprint
            && $credentials['mode'] === $payment->mode, 409, 'Le compte marchand ou le mode a changé. Rapprochez les paiements avant la bascule.');
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->post(self::HOST.$payment->mode.'/payment/'.rawurlencode($payment->external_reference).'/checking-payment');
        } catch (ConnectionException) {
            abort(502, 'Vérification EasyPay temporairement indisponible.');
        }
        abort_unless($response->successful() && is_array($response->json()), 502, 'Statut EasyPay non confirmé.');

        return $response->json();
    }

    public function checkoutUrl(Payment $payment): ?string
    {
        if (! $payment->is_active || $payment->state !== 'pending' || ! $payment->external_reference) {
            return null;
        }

        return self::HOST.$payment->mode.'/payment/initialization?reference='.rawurlencode($payment->external_reference);
    }
}
