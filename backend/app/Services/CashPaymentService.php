<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CashPaymentService
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function select(Order $order, int $actor): Payment
    {
        return DB::transaction(function () use ($order, $actor): Payment {
            Restaurant::lockForUpdate()->findOrFail($order->restaurant_id);
            $record = Order::lockForUpdate()->findOrFail($order->id);
            abort_if(in_array($record->status, ['cancelled', 'rejected'], true), 409, 'Commande clôturée.');
            $payment = Payment::where('order_id', $record->id)->where('is_active', true)->lockForUpdate()->first();
            if ($payment?->mode === 'cash') {
                return $payment;
            }
            abort_if($record->payment_state === 'paid' || Money::minor($record->total) === 0, 409, 'Aucun règlement supplémentaire nécessaire.');
            if ($payment) {
                // Une tentative réelle incertaine doit être rapprochée avant un autre encaissement.
                $safe = $payment->mode === 'sandbox' || in_array($payment->state, ['declined', 'cancelled'], true);
                abort_unless($safe && $payment->state !== 'paid', 409, 'Un paiement EasyPay est en cours ou incertain. Contactez le restaurant pour le rapprocher avant de choisir les espèces.');
                $payment->forceFill(['is_active' => false, 'archived_at' => now()])->save();
                Audit::record($actor, $record->restaurant_id, 'payment.archived_for_cash', (string) $payment->id);
            }
            $cash = new Payment;
            $cash->forceFill([
                'order_id' => $record->id,
                'restaurant_id' => $record->restaurant_id,
                'merchant_order_ref' => 'CASH'.Str::random(20),
                'merchant_fingerprint' => hash('sha256', 'cash:'.$record->restaurant_id),
                'mode' => 'cash',
                'amount' => $record->total,
                'currency' => 'CDF',
                'state' => 'pending',
            ])->save();
            Audit::record($actor, $record->restaurant_id, 'payment.cash.selected', (string) $cash->id);

            return $cash;
        }, 3);
    }

    public function receive(Order $order, int $actor, string $amount): Payment
    {
        return DB::transaction(function () use ($order, $actor, $amount): Payment {
            Restaurant::lockForUpdate()->findOrFail($order->restaurant_id);
            $record = Order::lockForUpdate()->findOrFail($order->id);
            abort_if(in_array($record->status, ['cancelled', 'rejected'], true), 409, 'Commande clôturée.');
            $payment = Payment::where('order_id', $record->id)->where('is_active', true)->lockForUpdate()->firstOrFail();
            abort_unless($payment->mode === 'cash', 409, 'Le client doit choisir le paiement en espèces.');
            abort_unless(Money::minor($amount) === Money::minor($record->total), 422, 'Confirmez la réception du montant total de la commande.');
            if ($payment->state === 'paid') {
                return $payment;
            }
            abort_unless($payment->state === 'pending' && $record->payment_state === 'unrecorded', 409, 'État de règlement incompatible.');
            $payment->forceFill(['state' => 'paid', 'verified_at' => now()])->save();
            $record->forceFill(['payment_state' => 'paid'])->save();
            DB::table('payment_events')->insert([
                'payment_id' => $payment->id,
                'digest' => hash('sha256', 'cash:'.$payment->id),
                'outcome' => 'paid',
                'verified_at' => now(),
            ]);
            Audit::record($actor, $record->restaurant_id, 'payment.cash.received', (string) $payment->id);
            $this->loyalty->reward($record);

            return $payment;
        }, 3);
    }
}
