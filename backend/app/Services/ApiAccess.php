<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\RestaurantRole;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApiAccess
{
    public function active(Restaurant $restaurant): void
    {
        abort_unless($restaurant->status === RestaurantStatus::Active, 404);
    }

    public function staff(User $user, Restaurant $restaurant, bool $management = false): void
    {
        $roles = $management
            ? [RestaurantRole::Manager, RestaurantRole::Administrator]
            : RestaurantRole::cases();
        abort_unless(app(RestaurantAccess::class)->allows($user, $restaurant, $roles), 403, 'Accès au restaurant refusé.');
    }

    public function platform(User $user): void
    {
        $allowed = $user->status === AccountStatus::Active
            && DB::table('platform_grants')->where('user_id', $user->id)->where('active', true)->exists();
        abort_unless($allowed, 403, 'Autorisation de plateforme requise.');
    }

    public function administrator(User $user, Restaurant $restaurant): void
    {
        abort_unless(app(RestaurantAccess::class)->allows($user, $restaurant, [RestaurantRole::Administrator]), 403, 'Administration de ce restaurant requise.');
    }
}
