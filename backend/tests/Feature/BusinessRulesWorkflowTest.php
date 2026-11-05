<?php

// Prise en main : Yedidia ; barèmes fictifs approuvés dans la seule base de test.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class BusinessRulesWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    private function rules(): array
    {
        return ['earn_amount' => '1000.00', 'redeem_points' => 15, 'redeem_value' => '1000.00', 'referral_points' => 3, 'expiry_days' => 30, 'require_paid' => false];
    }

    private function finish(Restaurant $restaurant, Order $order, User $server): void
    {
        foreach (['accepted', 'preparing', 'ready', 'completed'] as $index => $status) {
            $this->actingAs($server)->postJson('/api/v1/restaurants/'.$restaurant->id.'/orders/'.$order->id.'/transitions', [
                'status' => $status, 'expected_version' => $index + 1,
            ])->assertOk();
        }
    }

    public function test_local_program_reading_and_approval_are_reserved_to_management(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $root = '/api/v1/restaurants/'.$restaurant->id.'/loyalty-program';
        foreach ([User::factory()->create(), $this->employee($restaurant)] as $account) {
            $this->actingAs($account)->getJson($root)->assertForbidden();
            $this->postJson($root, [])->assertForbidden();
        }
        $this->actingAs($manager)->getJson($root)->assertOk()->assertJsonPath('data', null);
        $this->postJson($root, ['active' => true, 'approval' => false, 'rules' => $this->rules()])->assertUnprocessable();
        $this->assertDatabaseCount('loyalty_programs', 0);
        $this->postJson($root, ['active' => true, 'approval' => true, 'rules' => $this->rules()])->assertOk();
        $this->getJson($root)->assertOk()->assertJsonPath('data.rules.redeem_points', 15)->assertJsonPath('data.active', true);
        $this->getJson('/api/v1/restaurants/'.$other->id.'/loyalty-program')->assertForbidden();
    }

    public function test_points_reduce_the_price_and_are_restored_once_after_cancellation(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $customer = User::factory()->create();
        $this->actingAs($manager)->postJson('/api/v1/restaurants/'.$restaurant->id.'/loyalty-program', [
            'active' => true, 'approval' => true, 'rules' => $this->rules(),
        ])->assertOk();
        $item->forceFill(['price' => '7500.00'])->save();
        $first = $this->place($customer, $restaurant, $item);
        $this->finish($restaurant, $first, $manager);
        $this->actingAs($customer)->getJson('/api/v1/me/loyalty-accounts')->assertOk()->assertJsonPath('data.0.balance', 15);
        $discounted = $this->place($customer, $restaurant, $item, ['points' => 15]);
        $this->assertSame('1000.00', $discounted->discount);
        $this->assertSame('14000.00', $discounted->total);
        $this->getJson('/api/v1/me/loyalty-accounts')->assertOk()->assertJsonPath('data.0.balance', 0);
        $transition = '/api/v1/restaurants/'.$restaurant->id.'/orders/'.$discounted->id.'/transitions';
        $this->actingAs($manager)->postJson($transition, ['status' => 'cancelled', 'expected_version' => 1, 'reason' => 'Annulation fictive'])->assertOk();
        $this->postJson($transition, ['status' => 'cancelled', 'expected_version' => 2, 'reason' => 'Doublon'])->assertConflict();
        $this->actingAs($customer)->getJson('/api/v1/me/loyalty-accounts')->assertOk()->assertJsonPath('data.0.balance', 15);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_used_policy_cannot_be_rewritten_but_can_be_deactivated(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $root = '/api/v1/restaurants/'.$restaurant->id.'/loyalty-program';
        $body = ['active' => true, 'approval' => true, 'rules' => $this->rules()];
        $this->actingAs($manager)->postJson($root, $body)->assertOk();
        $this->place(User::factory()->create(), $restaurant, $item);
        $changed = $body;
        $changed['rules']['redeem_points'] = 1;
        $this->postJson($root, $changed)->assertConflict();
        $this->postJson($root, array_replace($body, ['active' => false]))->assertOk();
        $this->getJson($root)->assertOk()->assertJsonPath('data.active', false)->assertJsonPath('data.rules.redeem_points', 15);
    }

    public function test_referral_is_rewarded_only_once_in_its_own_program(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        [$other, $otherItem] = $this->restaurantWithMenu();
        $server = $this->employee($restaurant);
        $otherServer = $this->employee($other);
        $referrer = User::factory()->create();
        $customer = User::factory()->create();
        $program = $this->program($restaurant, $server);
        $this->program($other, $otherServer);
        $codeUrl = '/api/v1/restaurants/'.$restaurant->id.'/referral-code';
        $code = $this->actingAs($referrer)->postJson($codeUrl)->assertOk()->json('data.code');
        $this->postJson($codeUrl)->assertOk()->assertJsonPath('data.code', $code);
        $this->actingAs($customer)->postJson('/api/v1/me/referrals', ['code' => $code])->assertOk();
        $this->postJson('/api/v1/me/referrals', ['code' => $code])->assertOk();
        $this->assertDatabaseCount('referrals', 1);
        $foreignOrder = $this->place($customer, $other, $otherItem);
        $this->finish($other, $foreignOrder, $otherServer);
        $this->assertDatabaseHas('referrals', ['program_id' => $program, 'rewarded_at' => null]);
        $first = $this->place($customer, $restaurant, $item);
        $this->finish($restaurant, $first, $server);
        $second = $this->place($customer, $restaurant, $item);
        $this->finish($restaurant, $second, $server);
        $this->actingAs($referrer)->getJson('/api/v1/me/loyalty-accounts')->assertOk()->assertJsonPath('data.0.balance', 3);
        $this->assertDatabaseHas('referrals', ['program_id' => $program, 'qualifying_order_id' => $first->id]);
        $this->assertSame(1, DB::table('loyalty_entries')->where('kind', 'earn')->where('event_key', 'like', 'referral:%')->count());
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_served_unpaid_orders_do_not_earn_when_paid_is_required(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $server = $this->employee($restaurant);
        $program = $this->program($restaurant, $server);
        $rules = $this->rules();
        $rules['require_paid'] = true;
        DB::table('loyalty_programs')->where('id', $program)->update(['rules' => json_encode($rules)]);
        $order = $this->place(User::factory()->create(), $restaurant, $item);
        $this->finish($restaurant, $order, $server);
        $this->assertDatabaseCount('loyalty_entries', 0);
        $this->assertDatabaseCount('payments', 0);
    }
}
