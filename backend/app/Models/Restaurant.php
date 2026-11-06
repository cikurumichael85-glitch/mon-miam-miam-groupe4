<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use App\Enums\RestaurantStatus;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Le statut et la version des paramètres exigent une affectation explicite côté serveur.
#[Fillable(['name', 'timezone', 'address', 'delivery_enabled'])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => RestaurantStatus::class,
            'delivery_enabled' => 'boolean',
            'delivery_fee' => 'decimal:2',
            'settings_version' => 'integer',
        ];
    }

    /** @return HasMany<RestaurantMembership, $this> */
    public function memberships(): HasMany
    {
        // Cette relation décrit le personnel ; elle n'accorde pas de permission à elle seule.
        return $this->hasMany(RestaurantMembership::class);
    }
}
