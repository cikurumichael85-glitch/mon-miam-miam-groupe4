<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class CashPaymentTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_cash_requires_receipt_by_the_correct_restaurant_and_is_idempotent(): void
    {
        Http::preventStrayRequests();
        [$restaurant, $item] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $order = $this->place($customer, $restaurant, $item);
        $select = '/api/v1/me/orders/'.$order->id.'/cash-payment';
        $confirm = '/api/v1/restaurants/'.$restaurant->id.'/orders/'.$order->id.'/cash-payment/confirm';
        $body = ['amount' => $order->total, 'received' => true];

        $this->actingAs($customer)->postJson($select)->assertOk()->assertJsonPath('data.state', 'pending');
        $this->postJson($select)->assertOk();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('unrecorded', $order->fresh()->payment_state);
        $this->postJson($confirm, $body)->assertForbidden();
        $this->actingAs($this->employee($other))->postJson($confirm, $body)->assertForbidden();
        $staff = $this->employee($restaurant);
        $this->actingAs($staff)->postJson($confirm, ['amount' => '1.00', 'received' => true])->assertUnprocessable();
        $this->postJson($confirm, ['amount' => $order->total, 'received' => false])->assertUnprocessable();
        $this->postJson($confirm, $body)->assertOk()->assertJsonPath('data.state', 'paid');
        $this->postJson($confirm, $body)->assertOk();
        $this->assertSame('paid', $order->fresh()->payment_state);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertSame(1, DB::table('audit_events')->where('action', 'payment.cash.received')->where('actor_id', $staff->id)->count());
        $this->getJson('/api/v1/restaurants/'.$restaurant->id.'/statistics?period=week')
            ->assertOk()->assertJsonPath('data.cash_paid_orders', 1)
            ->assertJsonPath('data.cash_paid_total', (string) (int) $order->total);
        $this->getJson('/api/v1/restaurants/'.$restaurant->id.'/orders/'.$order->id)
            ->assertOk()->assertJsonPath('cash_receipt.actor_name', $staff->name);
        Http::assertNothingSent();
    }

    public function test_uncertain_online_payment_cannot_be_replaced_with_cash(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $order = $this->place($customer, $restaurant, $item);
        $payment = new Payment;
        $payment->forceFill([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'merchant_order_ref' => 'MMMTEST123456',
            'merchant_fingerprint' => hash('sha256', 'test'),
            'mode' => 'v1',
            'amount' => $order->total,
            'state' => 'unknown',
        ])->save();
        $this->actingAs($customer)->postJson('/api/v1/me/orders/'.$order->id.'/cash-payment')->assertConflict();
        $this->assertTrue($payment->fresh()->is_active);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_order_and_cash_choice_are_saved_together_on_confirmation(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $base = '/api/v1/restaurants/'.$restaurant->id;
        $quote = $this->actingAs($customer)->postJson($base.'/order-quotes', $this->cart($item))->assertCreated()->json('data.id');
        $body = ['quote_id' => $quote, 'idempotency_key' => (string) Str::uuid(), 'payment_method' => 'cash'];
        $id = $this->postJson($base.'/orders', $body)->assertCreated()
            ->assertJsonPath('payment.mode', 'cash')->assertJsonPath('payment.state', 'pending')->json('data.id');
        $this->postJson($base.'/orders', $body)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertDatabaseHas('payments', ['order_id' => $id, 'mode' => 'cash', 'state' => 'pending']);
        $this->assertDatabaseCount('payments', 1);
        $this->getJson('/api/v1/me/orders/'.$id)->assertOk()->assertJsonPath('payment.mode', 'cash');
        $this->postJson('/api/v1/me/orders/'.$id.'/payment/verify')->assertConflict();
    }
}
