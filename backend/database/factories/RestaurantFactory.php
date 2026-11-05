<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Database\Factories;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Restaurant> */
class RestaurantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        // Valeurs fictives pour les tests, sans création automatique de restaurant réel.
        return [
            'name' => 'Restaurant '.fake()->unique()->word(),
            'status' => RestaurantStatus::Inactive,
            'timezone' => 'Africa/Kinshasa',
            'address' => 'Adresse fictive de démonstration',
            'delivery_enabled' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => RestaurantStatus::Active,
        ]);
    }
}
