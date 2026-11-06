<?php

// Responsable de prise en main : Yedidia ; rapprochement relu avec Ryan.

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Throwable;

final class ReconcilePayments extends Command
{
    protected $signature = 'app:reconcile-payments';

    protected $description = 'Revérifier au maximum 25 paiements en attente, sans initier de transaction.';

    public function handle(PaymentService $payments): int
    {
        if (! config('easypay.enabled')) {
            $this->info('EasyPay désactivé : aucun appel effectué.');

            return self::SUCCESS;
        }
        $pending = Payment::where('is_active', true)->where('state', 'pending')->whereNotNull('external_reference')
            ->where('updated_at', '<=', now()->subMinutes(5))->oldest('updated_at')->limit(25)->get();
        $failed = false;
        foreach ($pending as $payment) {
            try {
                $payments->verify($payment);
            } catch (Throwable) {
                // Message sans URL, jeton, réponse du fournisseur ou données du client.
                $this->warn('Vérification différée du paiement interne '.$payment->id);
                Payment::where('id', $payment->id)->update(['updated_at' => now()]);
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
