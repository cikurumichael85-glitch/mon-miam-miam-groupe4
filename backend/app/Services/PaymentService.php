<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class PaymentService
{
    public function __construct(private EasyPayGateway $gateway, private LoyaltyService $loyalty) {}

    public function start(Order $order, string $name, ?string $phone = null): Payment
    {
        $credentials = $this->gateway->credentials($order->restaurant_id);
        $payment = DB::transaction(function () use ($order, $credentials, $phone): Payment {
            Restaurant::lockForUpdate()->findOrFail($order->restaurant_id);
            $record = Order::lockForUpdate()->findOrFail($order->id);
            abort_if(in_array($record->status, ['cancelled', 'rejected'], true), 409, 'Commande clôturée.');
            abort_if($record->total === '0.00', 422, 'Aucun encaissement nécessaire.');
            abort_if($record->payment_state === 'paid', 409, 'Cette commande est déjà réglée.');
            $existing = Payment::where('order_id', $record->id)->where('is_active', true)->lockForUpdate()->first();
            if ($existing && $existing->mode !== $credentials['mode']) {
                $this->archiveSandbox($existing, $record, $credentials['mode']);
                $existing = null;
            }
            if ($existing) {
                abort_if($phone !== null, 409, 'Une tentative de paiement existe déjà pour cette commande. Vérifiez son état ; aucune seconde demande n’a été envoyée.');

                return $existing;
            }
            $payment = new Payment;
            $payment->forceFill([
                'order_id' => $record->id,
                'restaurant_id' => $record->restaurant_id,
                // L'espace marchand impose une référence alphanumérique de 6 à 16 caractères.
                'merchant_order_ref' => 'MMM'.Str::random(13),
                'merchant_fingerprint' => hash('sha256', $credentials['cid']),
                'mode' => $credentials['mode'],
                'amount' => $record->total,
                'currency' => 'CDF',
                'state' => 'initializing',
            ]);
            $payment->save();

            return $payment;
        });
        if (! $payment->wasRecentlyCreated) {
            return $payment;
        }
        // Aucun appel réseau dans une transaction SQL, aucun retry automatique d'un paiement.
        try {
            $reference = $phone === null
                ? $this->gateway->initialize($payment, $name)
                : $this->gateway->initializeMobileMoney($payment, $name, $phone);
            $payment->external_reference = $reference;
            $payment->state = 'pending';
            $payment->save();
        } catch (Throwable $exception) {
            Payment::where('id', $payment->id)->update(['state' => 'unknown']);
            throw $exception;
        }

        return $payment;
    }

    private function archiveSandbox(Payment $payment, Order $order, string $mode): void
    {
        // Garder l'identifiant, la référence et les événements de l'essai de test.
        $replaceable = $payment->mode === 'sandbox' && $mode === 'v1'
            && in_array($payment->state, ['pending', 'unknown', 'declined', 'cancelled'], true);
        abort_unless($replaceable, 409, 'Cette tentative doit être rapprochée avant un nouveau paiement.');
        $payment->is_active = false;
        $payment->archived_at = now();
        $payment->save();
        Audit::record($order->customer_id, $order->restaurant_id, 'payment.sandbox.archived', (string) $payment->id);
    }

    public function verify(Payment $payment): Payment
    {
        abort_unless($payment->is_active, 409, 'Cette ancienne tentative est archivée ; aucun règlement de la commande ne sera modifié.');
        abort_unless($payment->external_reference, 409, 'Référence inconnue : rapprochement marchand nécessaire.');
        if ($payment->state === 'paid') {
            return $payment;
        }
        $remote = $this->gateway->check($payment);
        $this->assertMatch($payment, $remote);

        return DB::transaction(function () use ($payment, $remote): Payment {
            Restaurant::lockForUpdate()->findOrFail($payment->restaurant_id);
            $order = Order::lockForUpdate()->findOrFail($payment->order_id);
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            abort_unless($payment->is_active, 409, 'La tentative a été archivée pendant la vérification.');
            $this->assertMatch($payment, $remote);
            if ($payment->state === 'paid') {
                return $payment;
            }
            $status = $remote['payment']['status'];
            $payment->state = match ($status) {
                'INIT' => 'pending',
                'SUCCESS' => in_array($order->status, ['cancelled', 'rejected'], true) ? 'review' : 'paid',
                'CANCELED' => 'cancelled',
                'DECLINED' => 'declined',
                default => 'pending',
            };
            $payment->verified_at = now();
            $payment->save();
            if ($payment->state === 'paid') {
                $order->payment_state = 'paid';
                $order->save();
                $this->loyalty->reward($order);
            }
            DB::table('payment_events')->insertOrIgnore([
                'payment_id' => $payment->id,
                'digest' => hash('sha256', $payment->external_reference.'|'.$status),
                'outcome' => $payment->state,
                'verified_at' => now(),
            ]);

            return $payment;
        }, 3);
    }

    private function assertMatch(Payment $payment, array $remote): void
    {
        $status = $remote['payment']['status'] ?? null;
        // INIT identifie une transaction, pas encore un reçu de règlement.
        // Toute réponse autre que INIT conserve la vérification stricte du reçu.
        $receiptMatches = $status === 'INIT'
            || ($remote['payment']['reference'] ?? null) === $payment->external_reference;
        $matches = ($remote['transaction']['order_ref'] ?? null) === $payment->merchant_order_ref
            && ($remote['transaction']['reference'] ?? null) === $payment->external_reference
            && $receiptMatches
            && is_string($status);
        abort_unless($matches, 502, 'La confirmation ne correspond pas à cette commande.');
        // Le contrat publié ne renvoie pas montant/devise ; contrôler ces champs s'ils existent.
        $currency = $remote['transaction']['currency'] ?? $payment->currency;
        $amount = $remote['transaction']['amount'] ?? $payment->amount;
        abort_unless($currency === 'CDF' && Money::minor((string) $amount) === Money::minor($payment->amount), 502, 'Montant ou devise de confirmation incohérent.');
    }

    public function reconcile(Payment $payment, string $reference): Payment
    {
        abort_unless($payment->is_active, 409, 'Cette tentative est archivée.');
        abort_if($payment->external_reference !== null, 409, 'Référence déjà connue : utilisez la vérification.');
        $payment->external_reference = $reference;
        $remote = $this->gateway->check($payment);
        $this->assertMatch($payment, $remote);
        DB::transaction(function () use ($payment, $reference): void {
            $record = Payment::lockForUpdate()->findOrFail($payment->id);
            abort_unless($record->is_active, 409, 'Cette tentative vient d’être archivée.');
            abort_if($record->external_reference !== null, 409, 'Le paiement vient d’être rapproché.');
            $record->external_reference = $reference;
            $record->state = 'pending';
            $record->save();
        });

        return $this->verify($payment->fresh());
    }
}
