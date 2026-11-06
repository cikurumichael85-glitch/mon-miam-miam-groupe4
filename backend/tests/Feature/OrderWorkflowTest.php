<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_confirmation_is_idempotent_and_uses_server_prices(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $quote = app(QuoteService::class)->create($restaurant, $user->id, $this->cart($item));
        $body = ['quote_id' => $quote->id, 'idempotency_key' => (string) Str::uuid(), 'total' => '1.00'];
        $url = '/api/v1/restaurants/'.$restaurant->id.'/orders';
        $first = $this->actingAs($user)->postJson($url, $body)->assertCreated()->assertJsonPath('data.total', '5001.00');
        $this->postJson($url, $body)->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_lines', 1);
    }

    public function test_quote_expiry_preserves_the_same_instant_after_storage(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $quote = app(QuoteService::class)->create($restaurant, $customer->id, $this->cart($item));
        $stored = $quote->fresh();

        $this->assertTrue($stored->expires_at->isFuture());
        $this->assertEqualsWithDelta(600, now()->diffInSeconds($stored->expires_at), 2);
    }

    public function test_a_changed_price_rejects_the_old_quote_without_writes(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $quote = app(QuoteService::class)->create($restaurant, $user->id, $this->cart($item));
        $item->price = '3000.00';
        $item->version++;
        $item->save();
        $this->actingAs($user)->postJson('/api/v1/restaurants/'.$restaurant->id.'/orders', [
            'quote_id' => $quote->id,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertConflict();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_foreign_item_and_another_customers_order_are_hidden(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $owner = User::factory()->create();
        $order = $this->place($owner, $restaurant, $item);
        $this->actingAs(User::factory()->create())->getJson('/api/v1/me/orders/'.$order->id)->assertNotFound();
        $this->postJson('/api/v1/restaurants/'.$other->id.'/order-quotes', $this->cart($item))->assertNotFound();
    }

    public function test_transitions_check_version_role_and_delivery_mode(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $order = $this->place(User::factory()->create(), $restaurant, $item);
        $employee = $this->employee($restaurant);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/orders/'.$order->id.'/transitions';
        $this->actingAs($employee)->postJson($url, ['status' => 'cancelled', 'reason' => 'Test', 'expected_version' => 1])->assertForbidden();
        $this->postJson($url, ['status' => 'accepted', 'expected_version' => 1])->assertOk();
        $this->postJson($url, ['status' => 'preparing', 'expected_version' => 1])->assertConflict();
        $this->postJson($url, ['status' => 'preparing', 'expected_version' => 2])->assertOk();
        $this->postJson($url, ['status' => 'ready', 'expected_version' => 3])->assertOk();
        $this->postJson($url, ['status' => 'out_for_delivery', 'expected_version' => 4])->assertConflict();
        $this->postJson($url, ['status' => 'completed', 'expected_version' => 4])->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_local_management_cannot_create_a_platform_restaurant(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $this->actingAs($manager)->postJson('/api/v1/platform/restaurants', [])->assertForbidden();
    }

    public function test_non_numeric_route_identifiers_are_not_server_errors(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/v1/me/orders/not-an-id')->assertNotFound();
        $this->getJson('/api/v1/restaurants/999999999999999999999/menu')->assertNotFound();
    }
}
