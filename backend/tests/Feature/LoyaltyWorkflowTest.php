<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class LoyaltyWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_completion_rewards_only_once(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $this->program($restaurant, User::factory()->create());
        $order = $this->place($customer, $restaurant, $item);
        $order->status = 'completed';
        $order->save();
        DB::transaction(function () use ($order): void {
            app(LoyaltyService::class)->reward($order);
            app(LoyaltyService::class)->reward($order);
        });
        $this->assertDatabaseCount('loyalty_entries', 1);
        $this->assertDatabaseHas('loyalty_entries', ['order_id' => $order->id, 'delta' => 5]);
    }

    public function test_expiration_consumes_only_unspent_points_and_is_idempotent(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $program = $this->program($restaurant, User::factory()->create());
        $loyalty = app(LoyaltyService::class);
        $account = DB::transaction(function () use ($loyalty, $program, $customer): object {
            DB::table('loyalty_programs')->where('id', $program)->lockForUpdate()->first();
            $account = $loyalty->account($program, $customer->id);
            $loyalty->credit($account->id, null, 'fixture', 10, ['expiry_days' => 1]);

            return $account;
        });
        $this->place($customer, $restaurant, $item, ['points' => 5]);
        $this->travel(2)->days();
        DB::transaction(function () use ($loyalty, $account): void {
            $loyalty->expire($account->id);
            $loyalty->expire($account->id);
        });
        $this->assertSame(0, (int) DB::table('loyalty_entries')->where('account_id', $account->id)->sum('delta'));
        $this->assertDatabaseHas('loyalty_entries', ['kind' => 'expire', 'delta' => -5]);
        $this->assertDatabaseCount('loyalty_allocations', 2);
    }

    public function test_self_referral_is_rejected(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $this->program($restaurant, User::factory()->create());
        $code = $this->actingAs($customer)->postJson('/api/v1/restaurants/'.$restaurant->id.'/referral-code')->assertOk()->json('data.code');
        $this->postJson('/api/v1/me/referrals', ['code' => $code])->assertUnprocessable();
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_two_quotes_cannot_spend_the_same_balance(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $program = $this->program($restaurant, User::factory()->create());
        $loyalty = app(LoyaltyService::class);
        DB::transaction(function () use ($loyalty, $program, $customer): void {
            DB::table('loyalty_programs')->where('id', $program)->lockForUpdate()->first();
            $account = $loyalty->account($program, $customer->id);
            $loyalty->credit($account->id, null, 'fixture', 5, ['expiry_days' => 30]);
        });
        $cart = $this->cart($item) + ['points' => 5];
        $first = app(QuoteService::class)->create($restaurant, $customer->id, $cart);
        $second = app(QuoteService::class)->create($restaurant, $customer->id, $cart);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/orders';
        $this->actingAs($customer)->postJson($url, ['quote_id' => $first->id, 'idempotency_key' => (string) Str::uuid()])->assertCreated();
        $this->postJson($url, ['quote_id' => $second->id, 'idempotency_key' => (string) Str::uuid()])->assertConflict();
        $this->assertDatabaseCount('orders', 1);
    }
}
