<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\RestaurantRole;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RestaurantAccess
{
    /**
     * Vérifier une permission locale, sans remplacer l'authentification de l'appelant.
     *
     * @param  list<RestaurantRole>  $allowedRoles  Rôles admis pour l'opération demandée.
     */
    public function allows(User $user, Restaurant $restaurant, array $allowedRoles): bool
    {
        if (! $user->exists || ! $restaurant->exists || $allowedRoles === []) {
            // Refuser par défaut un objet non persisté ou une opération sans rôles autorisés.
            return false;
        }

        // Relire les droits en base pour prendre en compte une révocation récente.
        return RestaurantMembership::query()
            // Les deux identifiants doivent correspondre au même rattachement actif.
            ->where('user_id', $user->getKey())
            ->where('restaurant_id', $restaurant->getKey())
            ->where('active', true)
            ->whereIn('role', $allowedRoles)
            ->whereHas('user', function (Builder $query): void {
                $query->where('status', AccountStatus::Active->value);
            })
            ->whereHas('restaurant', function (Builder $query): void {
                $query->where('status', RestaurantStatus::Active->value);
            })
            ->exists();
    }
}
