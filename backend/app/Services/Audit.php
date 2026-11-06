<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class Audit
{
    public static function record(int $actor, ?int $restaurant, string $action, string $target): void
    {
        // Journal minimal : aucune adresse, aucun mot de passe et aucun jeton de paiement.
        DB::table('audit_events')->insert([
            'actor_id' => $actor,
            'restaurant_id' => $restaurant,
            'action' => $action,
            'target' => $target,
            'metadata' => '{}',
            'occurred_at' => now(),
        ]);
    }
}
