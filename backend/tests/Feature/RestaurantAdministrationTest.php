<?php

// Prise en main : Ryan ; relecture Yedidia pour les accès du personnel.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\RestaurantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class RestaurantAdministrationTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_only_the_local_administrator_can_read_settings(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/settings';
        $this->actingAs($administrator)->getJson($url)->assertOk()
            ->assertJsonPath('data.name', $restaurant->name)->assertJsonCount(7, 'hours');
        $this->getJson('/api/v1/restaurants/'.$other->id.'/settings')->assertForbidden();
        $this->actingAs($this->employee($restaurant, RestaurantRole::Manager))->getJson($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
    }

    public function test_renaming_is_versioned_and_isolated_from_other_restaurants(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $originalOtherName = $other->name;
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/settings';
        // Comme le formulaire, lire la version effective depuis l'API avant de modifier.
        $version = $this->actingAs($administrator)->getJson($url)->assertOk()->json('data.settings_version');
        $payload = ['name' => 'Nouvelle table', 'address' => 'Adresse pédagogique', 'timezone' => 'Africa/Kinshasa', 'delivery_enabled' => false, 'delivery_fee' => null, 'expected_version' => $version];
        $this->actingAs($administrator)->patchJson($url, $payload)->assertOk()->assertJsonPath('data.name', 'Nouvelle table');
        $this->patchJson($url, $payload)->assertConflict();
        $this->assertSame($originalOtherName, $other->fresh()->name);
        $this->actingAs($this->employee($restaurant, RestaurantRole::Manager))->patchJson($url, $payload)->assertForbidden();
    }

    public function test_hours_can_be_configured_without_guessing_a_version(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/hours';
        $version = $this->actingAs($administrator)->getJson('/api/v1/restaurants/'.$restaurant->id.'/settings')->assertOk()->json('data.settings_version');
        $payload = ['expected_version' => $version, 'hours' => [['weekday' => 1, 'opens_at' => '09:00', 'closes_at' => '18:00']]];
        $this->actingAs($administrator)->patchJson($url, $payload)->assertOk();
        $this->getJson('/api/v1/restaurants/'.$restaurant->id.'/settings')->assertOk()
            ->assertJsonCount(1, 'hours')->assertJsonPath('hours.0.opens_at', '09:00');
        $this->patchJson($url, $payload)->assertConflict();
    }

    public function test_manager_assignment_cannot_create_an_administrator_or_change_another_restaurant(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $account = User::factory()->create();
        $password = $account->password;
        $url = '/api/v1/restaurants/'.$restaurant->id.'/managers';
        $payload = ['email' => $account->email, 'active' => true, 'role' => 'restaurant_admin'];
        $this->actingAs($administrator)->postJson($url, $payload)->assertOk();
        $this->assertDatabaseHas('restaurant_memberships', ['restaurant_id' => $restaurant->id, 'user_id' => $account->id, 'role' => 'manager', 'active' => true]);
        $this->assertDatabaseMissing('restaurant_memberships', ['restaurant_id' => $other->id, 'user_id' => $account->id]);
        $this->assertSame($password, $account->fresh()->password);
        $this->postJson('/api/v1/restaurants/'.$other->id.'/managers', $payload)->assertForbidden();
        $this->actingAs($account)->postJson($url, $payload)->assertForbidden();
        $this->actingAs($administrator)->postJson($url, ['email' => $administrator->email, 'active' => false])->assertForbidden();
    }

    public function test_a_revoked_manager_loses_access_and_unknown_accounts_are_not_created(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/managers';
        $this->actingAs($administrator)->postJson($url, ['email' => 'absent@example.test', 'active' => true])->assertUnprocessable();
        $this->assertDatabaseMissing('users', ['email' => 'absent@example.test']);
        $this->postJson($url, ['email' => $manager->email, 'active' => false])->assertOk();
        $this->assertFalse(RestaurantMembership::where('restaurant_id', $restaurant->id)->where('user_id', $manager->id)->firstOrFail()->active);
        $this->actingAs($manager)->getJson('/api/v1/restaurants/'.$restaurant->id.'/orders')->assertForbidden();
    }
}
