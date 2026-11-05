<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoyaltyService
{
    public function program(int $restaurant): ?object
    {
        // Le programme local a priorité ; le programme global est un choix explicite.
        return DB::table('loyalty_programs')->where('active', true)
            ->where('restaurant_id', $restaurant)->lockForUpdate()->first()
            ?? DB::table('loyalty_programs')->where('active', true)
                ->where('scope_key', 'platform')->lockForUpdate()->first();
    }

    public function discount(?object $program, int $user, int $points, int $subtotal): int
    {
        if ($points === 0) {
            return 0;
        }
        abort_unless($program, 422, 'Aucun programme actif pour ce restaurant.');
        $rules = json_decode($program->rules, true, flags: JSON_THROW_ON_ERROR);
        $account = $this->account($program->id, $user);
        $this->expire($account->id);
        $balance = (int) DB::table('loyalty_entries')->where('account_id', $account->id)->sum('delta');
        $step = $rules['redeem_points'];
        if ($points % $step !== 0 || $points > $balance) {
            throw ValidationException::withMessages(['points' => 'Points insuffisants ou quantité incompatible avec le barème.']);
        }
        $discount = intdiv($points, $step) * Money::minor($rules['redeem_value']);
        abort_if($discount > $subtotal, 422, 'La remise dépasse le montant des articles.');

        return $discount;
    }

    public function account(int $program, int $user): object
    {
        // L'appelant verrouille d'abord le programme ; création et journal sont atomiques.
        DB::table('loyalty_accounts')->insertOrIgnore([
            'program_id' => $program,
            'user_id' => $user,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('loyalty_accounts')->where('program_id', $program)
            ->where('user_id', $user)->lockForUpdate()->first();
    }

    public function expire(int $account): void
    {
        $credits = DB::table('loyalty_entries')->where('account_id', $account)->where('delta', '>', 0)
            ->whereNotNull('expires_at')->where('expires_at', '<=', now())->orderBy('id')->get();
        foreach ($credits as $credit) {
            $remaining = $this->remaining($credit);
            if ($remaining > 0) {
                $debit = $this->entry($account, null, 'expire:'.$credit->id, 'expire', -$remaining);
                $this->allocate($credit->id, $debit, $remaining);
            }
        }
    }

    public function redeem(Order $order): void
    {
        if ($order->points_used === 0) {
            return;
        }
        $account = $this->account($order->loyalty_program_id, $order->customer_id);
        $this->expire($account->id);
        $remaining = $order->points_used;
        $debit = $this->entry($account->id, $order->id, 'redeem:'.$order->id, 'redeem', -$remaining);
        $credits = DB::table('loyalty_entries')->where('account_id', $account->id)->where('delta', '>', 0)->orderBy('id')->get();
        foreach ($credits as $credit) {
            $used = min($remaining, $this->remaining($credit));
            if ($used > 0) {
                $this->allocate($credit->id, $debit, $used);
                $remaining -= $used;
            }
        }
        abort_if($remaining !== 0, 409, 'Le solde de points a changé.');
    }

    public function reward(Order $order): void
    {
        if (! $order->loyalty_program_id || $order->status !== 'completed') {
            return;
        }
        $program = DB::table('loyalty_programs')->where('id', $order->loyalty_program_id)->lockForUpdate()->first();
        $rules = json_decode($program->rules, true, flags: JSON_THROW_ON_ERROR);
        if ($rules['require_paid'] && $order->payment_state !== 'paid') {
            return;
        }
        $account = $this->account($program->id, $order->customer_id);
        $amount = Money::minor($order->subtotal) - Money::minor($order->discount);
        $points = intdiv($amount, Money::minor($rules['earn_amount']));
        $this->credit($account->id, $order->id, 'earn:'.$order->id, $points, $rules);
        $this->rewardReferrer($order, $program, $rules);
    }

    private function rewardReferrer(Order $order, object $program, array $rules): void
    {
        $referral = DB::table('referrals')->where('program_id', $program->id)
            ->where('referred_user_id', $order->customer_id)->whereNull('rewarded_at')->lockForUpdate()->first();
        if (! $referral) {
            return;
        }
        $prior = Order::where('customer_id', $order->customer_id)->where('loyalty_program_id', $program->id)
            ->where('status', 'completed')->where('id', '<>', $order->id)
            ->when($rules['require_paid'], fn ($query) => $query->where('payment_state', 'paid'))->exists();
        if ($prior) {
            return;
        }
        $code = DB::table('referral_codes')->find($referral->code_id);
        $account = $this->account($program->id, $code->owner_id);
        $this->credit($account->id, $order->id, 'referral:'.$referral->id, $rules['referral_points'], $rules);
        DB::table('referrals')->where('id', $referral->id)->update([
            'qualifying_order_id' => $order->id,
            'rewarded_at' => now(),
        ]);
    }

    public function restore(Order $order): void
    {
        if ($order->points_used === 0) {
            return;
        }
        $program = DB::table('loyalty_programs')->where('id', $order->loyalty_program_id)->lockForUpdate()->first();
        $account = $this->account($program->id, $order->customer_id);
        $debit = DB::table('loyalty_entries')->where('account_id', $account->id)->where('event_key', 'redeem:'.$order->id)->first();
        $allocations = DB::table('loyalty_allocations')->where('debit_id', $debit->id)->get();
        foreach ($allocations as $allocation) {
            $credit = DB::table('loyalty_entries')->find($allocation->credit_id);
            // Une annulation restitue les lots avec leur date initiale, sans prolongation cachée.
            $this->entry($account->id, $order->id, 'restore:'.$allocation->id, 'reverse', $allocation->points, $credit->expires_at);
        }
        $this->expire($account->id);
    }

    public function credit(int $account, ?int $order, string $key, int $points, array $rules): void
    {
        if ($points <= 0) {
            return;
        }
        $expires = now()->addDays($rules['expiry_days']);
        $this->entry($account, $order, $key, 'earn', $points, $expires);
    }

    private function remaining(object $credit): int
    {
        return $credit->delta - (int) DB::table('loyalty_allocations')->where('credit_id', $credit->id)->sum('points');
    }

    private function allocate(int $credit, int $debit, int $points): void
    {
        DB::table('loyalty_allocations')->insertOrIgnore(['credit_id' => $credit, 'debit_id' => $debit, 'points' => $points]);
    }

    private function entry(int $account, ?int $order, string $key, string $kind, int $delta, mixed $expires = null): int
    {
        $existing = DB::table('loyalty_entries')->where('account_id', $account)->where('event_key', $key)->first();
        if ($existing) {
            return $existing->id;
        }
        $id = DB::table('loyalty_entries')->insertGetId([
            'account_id' => $account,
            'order_id' => $order,
            'event_key' => $key,
            'kind' => $kind,
            'delta' => $delta,
            'expires_at' => $expires,
            'occurred_at' => now(),
        ]);
        DB::table('loyalty_accounts')->where('id', $account)->increment('version');

        return $id;
    }
}
