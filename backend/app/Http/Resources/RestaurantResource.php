<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Resources;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class RestaurantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        // Projection publique explicite : ne pas sérialiser le modèle entier ni son personnel.
        return [
            // Un identifiant bigint reste exact dans JavaScript lorsqu'il est transmis en texte.
            'id' => (string) $this->id,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'status' => $this->status->value,
            'delivery_enabled' => $this->delivery_enabled,
        ];
    }
}
