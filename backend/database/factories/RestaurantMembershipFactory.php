<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Database\Factories;

use App\Enums\RestaurantRole;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RestaurantMembership> */
class RestaurantMembershipFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        // Les tests activent explicitement les rattachements qui doivent autoriser un accès.
        return [
            'restaurant_id' => Restaurant::factory(),
            'user_id' => User::factory(),
            'role' => RestaurantRole::Employee,
            'active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'active' => true,
        ]);
    }
}
