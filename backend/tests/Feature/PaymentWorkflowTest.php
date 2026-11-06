<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    private function configureMerchant(int $restaurant): void
    {
        Http::preventStrayRequests();
        config(['easypay.enabled' => true, 'easypay.mode' => 'sandbox']);
        config(['easypay.merchants' => [$restaurant => ['cid' => 'cid-fictif', 'publishable_key' => 'cle-fictive']]]);
    }

    private function providerResponse(Payment $payment, string $status): array
    {
        return [
            'transaction' => ['order_ref' => $payment->merchant_order_ref, 'reference' => $payment->external_reference],
            'payment' => ['status' => $status, 'reference' => $payment->external_reference, 'channel' => 'CARD'],
        ];
    }

    public function test_initialization_is_not_payment_and_is_not_duplicated(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url)->assertOk()->assertJsonPath('data.state', 'pending');
        $this->postJson($url)->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->body(), '"amount":5001.00') && $request['currency'] === 'CDF');
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{16}$/', Payment::firstOrFail()->merchant_order_ref);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_a_forged_notification_cannot_override_the_server_check(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        Http::fake(['*/checking-payment' => Http::response($this->providerResponse($payment, 'DECLINED'))]);
        $this->postJson('/api/v1/payments/easypay/ipn', $this->providerResponse($payment, 'SUCCESS'))->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'state' => 'declined']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_server_success_is_applied_only_once(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        Http::fake(['*/checking-payment' => Http::response($this->providerResponse($payment, 'SUCCESS'))]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment/verify';
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'paid');
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'paid');
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'paid']);
    }

    public function test_mismatched_reference_is_rejected(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $data = $this->providerResponse(Payment::firstOrFail(), 'SUCCESS');
        $data['transaction']['order_ref'] = 'AUTRECOMMANDE';
        Http::fake(['*/checking-payment' => Http::response($data)]);
        $this->postJson('/api/v1/me/orders/'.$order->id.'/payment/verify')->assertStatus(502);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_an_initialization_timeout_does_not_trigger_a_second_charge(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(function () {
            throw new ConnectionException('Délai simulé');
        });
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url)->assertStatus(502);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'state' => 'unknown']);
        // Toute nouvelle requête réseau serait interdite par ce faux transport vide.
        Http::fake([]);
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'unknown');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_production_archives_the_sandbox_attempt_and_reuses_the_new_real_attempt(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake([
            '*/sandbox/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST']),
            '*/v1/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFREAL']),
        ]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url)->assertOk();
        $testPayment = Payment::where('order_id', $order->id)->firstOrFail();
        config(['easypay.mode' => 'v1']);
        $this->postJson($url)->assertOk()->assertJsonPath('data.mode', 'v1')->assertJsonPath('data.external_reference', 'REFREAL');
        $this->postJson($url)->assertOk()->assertJsonPath('data.external_reference', 'REFREAL');
        Http::assertSentCount(2);
        $this->assertDatabaseHas('payments', ['id' => $testPayment->id, 'mode' => 'sandbox', 'external_reference' => 'REFTEST', 'is_active' => false]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'mode' => 'v1', 'external_reference' => 'REFREAL', 'is_active' => true]);
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('audit_events', ['action' => 'payment.sandbox.archived', 'target' => (string) $testPayment->id]);
        $this->getJson('/api/v1/me/orders/'.$order->id)->assertOk()
            ->assertJsonPath('payment.mode', 'v1')
            ->assertJsonPath('payment_attempts.0.is_active', false)
            ->assertJsonPath('payment_attempts.1.is_active', true);
        $this->postJson('/api/v1/payments/easypay/ipn', $this->providerResponse($testPayment, 'SUCCESS'))->assertOk();
        Http::assertSentCount(2);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_new_production_payment_uses_the_real_endpoint_without_marking_it_paid(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        config(['easypay.mode' => 'v1']);
        Http::fake(['*/v1/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFREAL'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')
            ->assertOk()
            ->assertJsonPath('data.mode', 'v1')
            ->assertJsonPath('data.state', 'pending')
            ->assertJsonPath('checkout_url', 'https://www.e-com-easypay.com/v1/payment/initialization?reference=REFREAL');
        Http::assertSentCount(1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_mobile_money_sends_the_national_phone_and_exact_amount_without_redirect(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/sandbox/mobile-money/payment*' => Http::response(['code' => 1, 'reference' => 'MOBILETEST'])]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url, ['customer_phone' => '+243 980 123 456'])
            ->assertOk()
            ->assertJsonPath('notification_requested', true)
            ->assertJsonPath('checkout_url', null)
            ->assertJsonPath('data.state', 'pending');
        Http::assertSent(fn ($request) => $request['customer_phone'] === '980123456'
            && str_contains($request->body(), '"amount":5001.00')
            && ! isset($request['customer_email']));
        $this->postJson($url, ['customer_phone' => '0980123456'])->assertStatus(409);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_invalid_mobile_phone_creates_no_attempt(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        Http::preventStrayRequests();
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment', ['customer_phone' => '+33123456789'])->assertStatus(422);
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_mobile_rejection_explains_the_error_without_marking_the_order_paid(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/mobile-money/payment*' => Http::response(['code' => 0, 'message' => 'Opérateur momentanément indisponible.'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment', ['customer_phone' => '0980123456'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'EasyPay : Opérateur momentanément indisponible.');
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'state' => 'unknown']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_mobile_timeout_cannot_send_a_second_notification(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(function () {
            throw new ConnectionException('Délai simulé');
        });
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url, ['customer_phone' => '0980123456'])->assertStatus(502);
        $this->postJson($url, ['customer_phone' => '0980123456'])->assertStatus(409);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'state' => 'unknown']);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_mobile_request_cannot_target_another_customers_order(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $owner = User::factory()->create();
        $order = $this->place($owner, $restaurant, $item);
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create())->postJson('/api/v1/me/orders/'.$order->id.'/payment', ['customer_phone' => '0980123456'])->assertNotFound();
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_already_paid_order_cannot_start_another_production_payment(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $order->forceFill(['payment_state' => 'paid'])->save();
        $this->configureMerchant($restaurant->id);
        config(['easypay.mode' => 'v1']);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertStatus(409);
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_uncertain_real_payment_is_not_replaced_or_sent_twice(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        config(['easypay.mode' => 'v1']);
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('Délai simulé');
        });
        $url = '/api/v1/me/orders/'.$order->id.'/payment';
        $this->actingAs($user)->postJson($url)->assertStatus(502);
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'unknown');
        $this->assertSame(1, $attempts);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'mode' => 'v1', 'is_active' => true]);
    }

    public function test_an_archived_attempt_cannot_mark_an_order_paid_after_a_late_response(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        Http::fake(['*/checking-payment' => function () use ($payment) {
            Payment::where('id', $payment->id)->update(['is_active' => false, 'archived_at' => now()]);

            return Http::response($this->providerResponse($payment, 'SUCCESS'));
        }]);
        $this->postJson('/api/v1/me/orders/'.$order->id.'/payment/verify')->assertStatus(409);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_database_rejects_two_active_attempts_for_the_same_order(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $duplicate = Payment::firstOrFail()->replicate();
        $duplicate->merchant_order_ref = 'UNIQUEATTEMPT002';
        $duplicate->external_reference = null;
        $this->expectException(QueryException::class);
        $duplicate->save();
    }

    public function test_init_remains_pending_when_the_final_receipt_reference_is_not_yet_available(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        $sequence = Http::sequence();
        foreach ([null, '', 'RECEIPT-NOT-ISSUED'] as $reference) {
            $data = $this->providerResponse($payment, 'INIT');
            $data['payment']['reference'] = $reference;
            $sequence->push($data);
        }
        Http::fake(['*/checking-payment' => $sequence]);
        for ($index = 0; $index < 3; $index++) {
            $this->postJson('/api/v1/me/orders/'.$order->id.'/payment/verify')->assertOk()->assertJsonPath('data.state', 'pending');
        }
        $this->assertNotNull($payment->fresh()->verified_at);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_init_still_rejects_a_different_transaction_or_order_reference(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        $sequence = Http::sequence();
        foreach (['order_ref', 'reference'] as $field) {
            $data = $this->providerResponse($payment, 'INIT');
            $data['transaction'][$field] = 'OTHER-TRANSACTION';
            $data['payment']['reference'] = null;
            $sequence->push($data);
        }
        Http::fake(['*/checking-payment' => $sequence]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment/verify';
        $this->postJson($url)->assertStatus(502);
        $this->postJson($url)->assertStatus(502);
        $this->assertNull($payment->fresh()->verified_at);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }

    public function test_final_states_still_require_a_matching_payment_receipt(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        $sequence = Http::sequence();
        foreach (['SUCCESS', 'CANCELED', 'DECLINED'] as $status) {
            $data = $this->providerResponse($payment, $status);
            $data['payment']['reference'] = null;
            $sequence->push($data);
        }
        Http::fake(['*/checking-payment' => $sequence]);
        for ($index = 0; $index < 3; $index++) {
            $this->postJson('/api/v1/me/orders/'.$order->id.'/payment/verify')->assertStatus(502);
        }
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'state' => 'pending']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_init_is_followed_by_a_paid_state_only_after_a_matching_success(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        $init = $this->providerResponse($payment, 'INIT');
        $init['payment']['reference'] = null;
        Http::fake(['*/checking-payment' => Http::sequence()->push($init)->push($this->providerResponse($payment, 'SUCCESS'))]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment/verify';
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'pending');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
        $this->postJson($url)->assertOk()->assertJsonPath('data.state', 'paid');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'paid']);
    }

    public function test_init_does_not_bypass_amount_or_currency_checks(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $this->configureMerchant($restaurant->id);
        Http::fake(['*/payment/initialization*' => Http::response(['code' => 1, 'reference' => 'REFTEST'])]);
        $this->actingAs($user)->postJson('/api/v1/me/orders/'.$order->id.'/payment')->assertOk();
        $payment = Payment::firstOrFail();
        $sequence = Http::sequence();
        foreach ([['currency' => 'USD'], ['amount' => '1.00']] as $invalid) {
            $data = $this->providerResponse($payment, 'INIT');
            $data['payment']['reference'] = null;
            $data['transaction'] = array_merge($data['transaction'], $invalid);
            $sequence->push($data);
        }
        Http::fake(['*/checking-payment' => $sequence]);
        $url = '/api/v1/me/orders/'.$order->id.'/payment/verify';
        $this->postJson($url)->assertStatus(502);
        $this->postJson($url)->assertStatus(502);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_state' => 'unrecorded']);
    }
}
