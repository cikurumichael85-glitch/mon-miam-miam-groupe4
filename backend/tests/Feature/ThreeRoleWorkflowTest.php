<?php

// Recette du binôme C : comptes fictifs et base de test, sans appel marchand.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class ThreeRoleWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public static function serviceModes(): array
    {
        return ['sur place' => ['on_site'], 'livraison' => ['delivery']];
    }

    #[DataProvider('serviceModes')]
    public function test_administrator_customer_and_server_share_one_order_without_payment(string $mode): void
    {
        $this->travelTo(now()->setTime(12, 0));
        [$restaurant, $initialItem] = $this->restaurantWithMenu();
        $restaurant->forceFill(['delivery_enabled' => true, 'delivery_fee' => '1000.00'])->save();
        $administrator = $this->employee($restaurant, RestaurantRole::Administrator);
        $server = $this->employee($restaurant);
        $customer = User::factory()->create();
        $root = '/api/v1/restaurants/'.$restaurant->id;

        // L'administrateur crée la carte que le client utilisera réellement via l'API.
        $itemId = $this->actingAs($administrator)->postJson($root.'/items', [
            'category_id' => $initialItem->category_id,
            'name' => 'Riz aux haricots de recette',
            'description' => 'Article fictif de recette',
            'price' => '3000.00',
        ])->assertCreated()->json('data.id');
        $input = ['items' => [['item_id' => $itemId, 'quantity' => 2]], 'service_mode' => $mode, 'points' => 0];
        if ($mode === 'delivery') {
            $input['delivery'] = ['address' => 'Adresse fictive de recette', 'phone' => '000000000', 'instructions' => 'Entrée de test'];
        }
        $total = $mode === 'delivery' ? '7000.00' : '6000.00';
        $quoteId = $this->actingAs($customer)->postJson($root.'/order-quotes', $input)
            ->assertCreated()->assertJsonPath('data.payload.total', $total)->json('data.id');
        $confirmation = ['quote_id' => $quoteId, 'idempotency_key' => (string) Str::uuid()];
        $orderId = $this->postJson($root.'/orders', $confirmation)->assertCreated()
            ->assertJsonPath('data.payment_state', 'unrecorded')->json('data.id');
        $this->postJson($root.'/orders', $confirmation)->assertOk()->assertJsonPath('data.id', $orderId);
        $this->getJson($root.'/orders')->assertForbidden();

        $this->actingAs($server)->getJson($root.'/orders')->assertOk()->assertJsonPath('data.0.id', $orderId);
        $detail = $this->getJson($root.'/orders/'.$orderId)->assertOk()->assertJsonPath('lines.0.name', 'Riz aux haricots de recette');
        if ($mode === 'delivery') {
            $detail->assertJsonPath('data.delivery_snapshot.address', $input['delivery']['address'])
                ->assertJsonPath('data.delivery_snapshot.phone', '000000000');
        }
        $steps = ['accepted', 'preparing', 'ready'];
        if ($mode === 'delivery') {
            $steps[] = 'out_for_delivery';
        }
        $steps[] = 'completed';
        foreach ($steps as $index => $status) {
            $this->postJson($root.'/orders/'.$orderId.'/transitions', ['status' => $status, 'expected_version' => $index + 1])
                ->assertOk()->assertJsonPath('data.status', $status);
        }
        $this->actingAs($customer)->getJson('/api/v1/me/orders/'.$orderId)->assertOk()
            ->assertJsonPath('data.status', 'completed')->assertJsonPath('data.total', $total)
            ->assertJsonPath('data.payment_state', 'unrecorded')->assertJsonCount(count($steps) + 1, 'events');

        // L'archive retire l'article de la carte, sans altérer la commande déjà servie.
        $this->actingAs($administrator)->postJson($root.'/items/'.$itemId.'/archive', ['expected_version' => 1])->assertOk();
        $this->getJson($root.'/menu')->assertOk()->assertJsonMissing(['name' => 'Riz aux haricots de recette']);
        $this->actingAs($customer)->getJson('/api/v1/me/orders/'.$orderId)->assertOk()
            ->assertJsonPath('lines.0.name', 'Riz aux haricots de recette');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_customer_and_server_cannot_use_administrator_actions(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $root = '/api/v1/restaurants/'.$restaurant->id;
        $customer = User::factory()->create();
        $server = $this->employee($restaurant);
        foreach ([$customer, $server] as $account) {
            $this->actingAs($account)->postJson($root.'/items', [])->assertForbidden();
            $this->patchJson($root.'/items/'.$item->id, [])->assertForbidden();
            $this->postJson($root.'/items/'.$item->id.'/archive', [])->assertForbidden();
            $this->patchJson($root.'/settings', [])->assertForbidden();
        }
        $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'archived_at' => null]);
    }

    public function test_the_three_roles_can_login_and_logout_without_changing_permissions(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $accounts = [
            [$this->employee($restaurant, RestaurantRole::Administrator), 'restaurant_admin'],
            [$this->employee($restaurant), 'employee'],
            [User::factory()->create(), null],
        ];
        foreach ($accounts as [$account, $role]) {
            $password = Str::random(24);
            $account->forceFill(['password' => $password])->save();
            $response = $this->postJson('/api/v1/auth/login', ['email' => $account->email, 'password' => $password])
                ->assertOk()->assertJsonPath('data.id', $account->id)->assertJsonMissingPath('data.password');
            if ($role === null) {
                $response->assertJsonCount(0, 'data.memberships');
            } else {
                $response->assertJsonPath('data.memberships.0.role', $role);
            }
            $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $account->id);
            $this->postJson('/api/v1/auth/logout')->assertOk();
            $this->getJson('/api/v1/me')->assertUnauthorized();
        }
    }
}
