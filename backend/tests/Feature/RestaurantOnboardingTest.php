<?php

// Prise en main : Ryan ; isolation des établissements et attribution du créateur.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class RestaurantOnboardingTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    private function payload(): array
    {
        return ['name' => 'Nouvelle table de test', 'address' => 'Adresse fictive', 'timezone' => 'Africa/Kinshasa', 'delivery_enabled' => false, 'delivery_fee' => null];
    }

    public function test_clients_servers_and_managers_cannot_add_a_restaurant(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $this->postJson('/api/v1/me/restaurants', $this->payload())->assertUnauthorized();
        foreach ([User::factory()->create(), $this->employee($restaurant), $this->employee($restaurant, RestaurantRole::Manager)] as $user) {
            $this->actingAs($user)->postJson('/api/v1/me/restaurants', $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('restaurants', 1);
    }

    public function test_administrator_becomes_local_owner_without_receiving_platform_or_foreign_access(): void
    {
        [$original] = $this->restaurantWithMenu();
        [$foreign] = $this->restaurantWithMenu();
        $owner = $this->employee($original, RestaurantRole::Administrator);
        $other = $this->employee($foreign, RestaurantRole::Administrator);
        $response = $this->actingAs($owner)->postJson('/api/v1/me/restaurants', $this->payload() + ['user_id' => $other->id, 'role' => 'platform_admin']);
        $response->assertCreated();
        $id = $response->json('data.id');
        $this->assertDatabaseHas('restaurant_memberships', ['restaurant_id' => $id, 'user_id' => $owner->id, 'role' => 'restaurant_admin', 'active' => true]);
        $this->assertDatabaseMissing('restaurant_memberships', ['restaurant_id' => $id, 'user_id' => $other->id]);
        $this->assertDatabaseCount('platform_grants', 0);
        $this->getJson('/api/v1/restaurants/'.$id.'/settings')->assertOk();
        $this->getJson('/api/v1/restaurants/'.$foreign->id.'/settings')->assertForbidden();
        $this->actingAs($other)->getJson('/api/v1/restaurants/'.$id.'/settings')->assertForbidden();
        $this->getJson('/api/v1/restaurants')->assertOk()->assertJsonFragment(['name' => 'Nouvelle table de test']);
        $this->assertDatabaseMissing('restaurant_hours', ['restaurant_id' => $id]);
        $this->assertDatabaseMissing('menu_items', ['restaurant_id' => $id]);
    }

    public function test_revoked_administrator_and_invalid_details_cannot_create_an_establishment(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $owner = $this->employee($restaurant, RestaurantRole::Administrator);
        $this->actingAs($owner)->postJson('/api/v1/me/restaurants', array_replace($this->payload(), ['name' => '']))->assertUnprocessable();
        $owner->memberships()->update(['active' => false]);
        $this->postJson('/api/v1/me/restaurants', $this->payload())->assertForbidden();
        $this->assertSame(1, Restaurant::count());
    }
}
