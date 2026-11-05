<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ErasureService
{
    public function execute(int $requestId, int $actor): void
    {
        $days = config('privacy.retention_days');
        abort_unless(is_numeric($days) && (int) $days >= 1, 409, 'Politique de conservation non configurée.');
        DB::transaction(function () use ($requestId, $actor, $days): void {
            $request = DB::table('privacy_requests')->where('id', $requestId)->lockForUpdate()->first();
            abort_unless($request, 404);
            abort_unless($request->status === 'in_review', 409, 'La demande doit être instruite avant traitement.');
            $user = User::lockForUpdate()->findOrFail($request->user_id);
            $this->assertEligible($user->id, (int) $days);
            $this->anonymize($user);
            DB::table('privacy_requests')->where('id', $requestId)->update([
                'status' => 'completed',
                'resolution' => 'Anonymisation applicative réalisée selon la durée de conservation configurée.',
                'resolved_at' => now(),
            ]);
            Audit::record($actor, null, 'privacy.anonymized', (string) $requestId);
        });
    }

    private function assertEligible(int $user, int $days): void
    {
        $retained = Order::where('customer_id', $user)->where(function ($query) use ($days): void {
            $query->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
                ->orWhere('created_at', '>', now()->subDays($days));
        })->exists();
        $pendingPayment = DB::table('payments')->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.is_active', true)
            ->where('orders.customer_id', $user)->whereIn('payments.state', ['unknown', 'initializing', 'pending', 'review'])->exists();
        abort_if($retained || $pendingPayment, 409, 'Commande en cours, délai de conservation ou paiement à rapprocher.');
    }

    private function anonymize(User $user): void
    {
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $user->name = 'Compte anonymisé';
        $user->email = Str::uuid().'@example.invalid';
        $user->phone = null;
        $user->delivery_address = null;
        $user->public_alias = null;
        $user->ranking_opt_in = false;
        $user->analytics_consent = false;
        $user->password = Str::random(64);
        $user->remember_token = null;
        $user->email_verified_at = null;
        $user->status = AccountStatus::Suspended;
        $user->save();
        DB::table('restaurant_memberships')->where('user_id', $user->id)->update(['active' => false]);
        DB::table('platform_grants')->where('user_id', $user->id)->update(['active' => false]);
        DB::table('orders')->where('customer_id', $user->id)->update(['delivery_snapshot' => null]);
        DB::table('order_quotes')->where('customer_id', $user->id)->update(['payload' => '{}']);
        DB::table('claims')->where('author_id', $user->id)->update(['subject' => 'Données anonymisées', 'message' => 'Données anonymisées']);
        DB::table('claim_replies')->where('author_id', $user->id)->update(['body' => 'Données anonymisées']);
        DB::table('reviews')->where('user_id', $user->id)->update(['visible' => false, 'comment' => 'Données anonymisées']);
        // Les identifiants internes et les montants restent pour la cohérence comptable.
    }
}
