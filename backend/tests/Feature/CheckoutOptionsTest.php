<?php

// Prise en main : Yedidia ; relecture Ryan pour les horaires et l’isolation des restaurants.

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class CheckoutOptionsTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_disabled_payment_is_explicit_and_never_calls_the_provider(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        config(['easypay.enabled' => false]);
        Http::fake();
        $this->getJson('/api/v1/restaurants/'.$restaurant->id.'/checkout-options')->assertOk()
            ->assertJsonPath('payment.available', false)->assertJsonPath('payment.networks', [])
            ->assertJsonCount(7, 'hours');
        Http::assertNothingSent();
    }

    public function test_public_options_never_expose_merchant_credentials_and_filter_unconfirmed_networks(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        config(['easypay.enabled' => true, 'easypay.mode' => 'sandbox']);
        config(['easypay.merchants' => [$restaurant->id => ['cid' => 'identifiant-fictif-ne-pas-exposer', 'publishable_key' => 'cle-fictive-ne-pas-exposer', 'confirmed_networks' => ['mpesa', 'reseau-non-valide']]]]);
        Http::fake();
        $response = $this->getJson('/api/v1/restaurants/'.$restaurant->id.'/checkout-options')->assertOk()
            ->assertJsonPath('payment.available', true)->assertJsonPath('payment.test_mode', true)
            ->assertJsonPath('payment.networks', ['mpesa']);
        $this->assertStringNotContainsString('identifiant-fictif-ne-pas-exposer', $response->getContent());
        $this->assertStringNotContainsString('cle-fictive-ne-pas-exposer', $response->getContent());
        $this->getJson('/api/v1/restaurants/'.$other->id.'/checkout-options')->assertOk()->assertJsonPath('payment.available', false);
        Http::assertNothingSent();
    }
}
