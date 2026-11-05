<?php

// Prise en main : Ryan ; recette croisée avec Yedidia, sans données réelles.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class MultiRestaurantWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_new_restaurant_has_its_own_menu_hours_team_and_orders(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        [$original, $originalItem] = $this->restaurantWithMenu();
        $owner = $this->employee($original, RestaurantRole::Administrator);
        $server = $this->employee($original);
        $customer = User::factory()->create();
        $restaurantId = $this->actingAs($owner)->postJson('/api/v1/me/restaurants', [
            'name' => 'Deuxième restaurant fictif',
            'address' => 'Adresse fictive B',
            'timezone' => 'Africa/Lubumbashi',
            'delivery_enabled' => false,
            'delivery_fee' => null,
        ])->assertCreated()->json('data.id');
        $root = '/api/v1/restaurants/'.$restaurantId;
        $this->getJson($root.'/menu')->assertOk()->assertJsonCount(0, 'categories')->assertJsonCount(0, 'items.data');
        $this->getJson($root.'/employees')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', $owner->id);
        $settings = $this->getJson($root.'/settings')->assertOk()->assertJsonCount(0, 'hours');
        $categoryId = $this->postJson($root.'/categories', ['name' => 'Plats B'])->assertCreated()->json('data.id');
        $itemId = $this->postJson($root.'/items', ['category_id' => $categoryId, 'name' => 'Plat B', 'price' => '4500.00'])->assertCreated()->json('data.id');
        $cart = ['items' => [['item_id' => $itemId, 'quantity' => 2]], 'service_mode' => 'on_site'];
        $this->actingAs($customer)->postJson($root.'/order-quotes', $cart)->assertUnprocessable();
        $hours = [];
        for ($day = 1; $day <= 7; $day++) {
            $hours[] = ['weekday' => $day, 'opens_at' => '00:00', 'closes_at' => '23:59'];
        }
        $this->actingAs($owner)->patchJson($root.'/hours', ['hours' => $hours, 'expected_version' => $settings->json('data.settings_version')])->assertOk();
        $this->getJson($root.'/checkout-options')->assertOk()->assertJsonPath('timezone', 'Africa/Lubumbashi')->assertJsonCount(7, 'hours');
        $this->getJson('/api/v1/restaurants/'.$original->id.'/menu')->assertOk()->assertJsonMissing(['name' => 'Plat B']);
        $this->getJson($root.'/menu')->assertOk()->assertJsonMissing(['name' => $originalItem->name]);

        $quoteId = $this->actingAs($customer)->postJson($root.'/order-quotes', $cart)->assertCreated()
            ->assertJsonPath('data.payload.total', '9000.00')->json('data.id');
        $orderId = $this->postJson($root.'/orders', ['quote_id' => $quoteId, 'idempotency_key' => (string) Str::uuid()])->assertCreated()->json('data.id');
        $this->actingAs($server)->getJson($root.'/orders')->assertForbidden();
        $passwordHash = $server->password;
        $this->actingAs($owner)->postJson($root.'/employees', ['name' => 'Nom non substitué', 'email' => $server->email])->assertCreated();
        $this->assertSame($passwordHash, $server->fresh()->password);
        $this->assertSame($server->name, $server->fresh()->name);
        $this->postJson($root.'/employees', ['name' => 'Autre nom', 'email' => $server->email])->assertConflict();
        $this->actingAs($server)->getJson($root.'/orders')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $orderId);
        $this->getJson('/api/v1/restaurants/'.$original->id.'/orders')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($owner)->patchJson($root.'/employees/'.$server->id, ['active' => false])->assertOk();
        $this->actingAs($server)->getJson($root.'/orders')->assertForbidden();
        $this->getJson('/api/v1/restaurants/'.$original->id.'/orders')->assertOk();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_foreign_menu_categories_and_orders_cannot_be_used_through_an_authorized_restaurant(): void
    {
        [$first, $firstItem] = $this->restaurantWithMenu();
        [$second, $secondItem] = $this->restaurantWithMenu();
        $owner = $this->employee($first, RestaurantRole::Administrator);
        $customer = User::factory()->create();
        $firstOrder = $this->place($customer, $first, $firstItem);
        $secondOrder = $this->place(User::factory()->create(), $second, $secondItem);
        $root = '/api/v1/restaurants/'.$first->id;
        $this->actingAs($owner)->postJson($root.'/items', ['category_id' => $secondItem->category_id, 'name' => 'Catégorie étrangère', 'price' => '1.00'])->assertNotFound();
        $this->patchJson($root.'/items/'.$secondItem->id.'/availability', ['available' => false, 'expected_version' => 1])->assertNotFound();
        $this->postJson($root.'/items/'.$secondItem->id.'/archive', ['expected_version' => 1])->assertNotFound();
        $this->getJson($root.'/orders')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $firstOrder->id);
        $this->getJson($root.'/orders/'.$secondOrder->id)->assertNotFound();
        $this->postJson($root.'/orders/'.$secondOrder->id.'/transitions', ['status' => 'accepted', 'expected_version' => 1])->assertNotFound();
        $this->actingAs($customer)->postJson($root.'/order-quotes', $this->cart($secondItem))->assertNotFound();
        $this->getJson('/api/v1/me/orders/'.$secondOrder->id)->assertNotFound();
        $this->assertTrue($secondItem->fresh()->available);
        $this->assertNull($secondItem->fresh()->archived_at);
        $this->assertSame('pending', $secondOrder->fresh()->status);
    }

    public function test_new_restaurant_cannot_inherit_another_restaurants_quote(): void
    {
        [$first, $item] = $this->restaurantWithMenu();
        [$second] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $quoteId = $this->actingAs($customer)->postJson('/api/v1/restaurants/'.$first->id.'/order-quotes', $this->cart($item))->assertCreated()->json('data.id');
        $this->postJson('/api/v1/restaurants/'.$second->id.'/orders', ['quote_id' => $quoteId, 'idempotency_key' => (string) Str::uuid()])->assertNotFound();
        $this->assertDatabaseCount('orders', 0);
    }
}
