<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Database\Seeders\RestaurantDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

// Éviter les comptes implicites, les doublons et les réactivations lors d'une démonstration.
class RestaurantDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_can_be_repeated_without_creating_accounts_or_duplicates(): void
    {
        $this->seed(RestaurantDemoSeeder::class);
        $this->seed(RestaurantDemoSeeder::class);

        $this->assertDatabaseCount('restaurants', 2);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('restaurant_memberships', 0);
        $this->assertDatabaseHas('restaurants', ['timezone' => 'Africa/Kinshasa']);
        $this->assertDatabaseHas('restaurants', ['timezone' => 'Africa/Lubumbashi']);
    }

    public function test_demo_seed_does_not_reactivate_an_existing_restaurant(): void
    {
        $this->seed(RestaurantDemoSeeder::class);
        $restaurant = Restaurant::query()->firstOrFail();
        $restaurant->status = RestaurantStatus::Inactive;
        $restaurant->save();

        $this->seed(RestaurantDemoSeeder::class);

        $this->assertSame(RestaurantStatus::Inactive, $restaurant->fresh()->status);
    }

    public function test_demo_seed_is_refused_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->expectException(LogicException::class);

        (new RestaurantDemoSeeder)->run();
    }
}
