<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Database\Seeders;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class RestaurantDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Les données fictives sont réservées au développement.');
        }

        DB::transaction(function (): void {
            // Créer les deux exemples ensemble ou annuler toute la création en cas d'erreur.
            $this->createRestaurant('Démonstration A — Kinshasa', 'Africa/Kinshasa');
            $this->createRestaurant('Démonstration B — Lubumbashi', 'Africa/Lubumbashi');
        });
    }

    private function createRestaurant(string $name, string $timezone): void
    {
        // Un second passage conserve les données existantes au lieu de les réactiver.
        $restaurant = Restaurant::query()->firstOrNew(
            ['name' => $name],
            [
                'timezone' => $timezone,
                'address' => 'Adresse fictive, à remplacer avant toute utilisation réelle.',
                'delivery_enabled' => false,
            ],
        );

        if ($restaurant->exists) {
            return;
        }

        $restaurant->status = RestaurantStatus::Active;
        $restaurant->save();
    }
}
