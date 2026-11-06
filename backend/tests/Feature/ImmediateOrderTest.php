<?php

// Prise en main : Ryan ; distinguer la commande immédiate d'une arrivée planifiée.

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class ImmediateOrderTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public static function openingBoundaries(): array
    {
        return [
            'samedi avant ouverture' => ['2026-10-24T07:59:00+01:00', 6, '08:00', false],
            'samedi ouverture' => ['2026-10-24T08:00:00+01:00', 6, '08:00', true],
            'samedi avant fermeture' => ['2026-10-24T19:59:00+01:00', 6, '08:00', true],
            'samedi fermeture' => ['2026-10-24T20:00:00+01:00', 6, '08:00', false],
            'dimanche avant ouverture' => ['2026-10-25T12:59:00+01:00', 7, '13:00', false],
            'dimanche ouverture' => ['2026-10-25T13:00:00+01:00', 7, '13:00', true],
            'dimanche avant fermeture' => ['2026-10-25T19:59:00+01:00', 7, '13:00', true],
            'dimanche fermeture' => ['2026-10-25T20:00:00+01:00', 7, '13:00', false],
        ];
    }

    #[DataProvider('openingBoundaries')]
    public function test_opening_includes_start_and_excludes_end(string $instant, int $weekday, string $opening, bool $allowed): void
    {
        $this->travelTo(Carbon::parse($instant));
        [$restaurant, $item] = $this->restaurantWithMenu();
        DB::table('restaurant_hours')->where('restaurant_id', $restaurant->id)->delete();
        DB::table('restaurant_hours')->insert(['restaurant_id' => $restaurant->id, 'weekday' => $weekday, 'opens_at' => $opening, 'closes_at' => '20:00']);
        $input = $this->cart($item);
        unset($input['arrival_at']);
        $this->actingAs(User::factory()->create())->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $input)
            ->assertStatus($allowed ? 201 : 422);
        $this->assertDatabaseCount('order_quotes', $allowed ? 1 : 0);
    }

    private function saturday(): array
    {
        $this->travelTo(Carbon::parse('2026-10-24T10:46:30+00:00'));
        [$restaurant, $item] = $this->restaurantWithMenu();
        DB::table('restaurant_hours')->where('restaurant_id', $restaurant->id)->delete();
        DB::table('restaurant_hours')->insert(['restaurant_id' => $restaurant->id, 'weekday' => 6, 'opens_at' => '08:00', 'closes_at' => '20:00']);

        return [$restaurant, $item];
    }

    public function test_the_same_instant_uses_each_restaurants_timezone(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25T11:30:00+00:00'));
        [$kinshasa, $kinshasaItem] = $this->restaurantWithMenu();
        [$lubumbashi, $lubumbashiItem] = $this->restaurantWithMenu();
        $lubumbashi->forceFill(['timezone' => 'Africa/Lubumbashi'])->save();
        DB::table('restaurant_hours')->whereIn('restaurant_id', [$kinshasa->id, $lubumbashi->id])->delete();
        foreach ([$kinshasa, $lubumbashi] as $restaurant) {
            DB::table('restaurant_hours')->insert(['restaurant_id' => $restaurant->id, 'weekday' => 7, 'opens_at' => '13:00', 'closes_at' => '20:00']);
        }
        $this->actingAs(User::factory()->create());
        foreach ([[$kinshasa, $kinshasaItem, 422], [$lubumbashi, $lubumbashiItem, 201]] as [$restaurant, $item, $status]) {
            $input = $this->cart($item);
            unset($input['arrival_at']);
            $this->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $input)->assertStatus($status);
        }
    }

    public function test_immediate_order_reaches_the_service_without_a_client_arrival_date(): void
    {
        [$restaurant, $item] = $this->saturday();
        $customer = User::factory()->create();
        $employee = $this->employee($restaurant);
        $cart = $this->cart($item);
        unset($cart['arrival_at']);
        $quote = $this->actingAs($customer)->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $cart)->assertCreated();
        // Attendre avant de confirmer ne transforme pas l'heure du formulaire en date passée.
        $this->travel(2)->minutes();
        $confirmation = ['quote_id' => $quote->json('data.id'), 'idempotency_key' => (string) Str::uuid()];
        $url = '/api/v1/restaurants/'.$restaurant->id.'/orders';
        $order = $this->postJson($url, $confirmation)->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.arrival_at', null);
        $this->postJson($url, $confirmation)->assertOk()->assertJsonPath('data.id', $order->json('data.id'));
        $this->assertDatabaseCount('orders', 1);
        $this->actingAs($employee)->getJson($url)->assertOk()->assertJsonFragment(['id' => $order->json('data.id')]);
        $this->travelBack();
    }

    public function test_planned_past_arrival_is_rejected_with_a_french_explanation(): void
    {
        [$restaurant, $item] = $this->saturday();
        $cart = array_replace($this->cart($item), ['arrival_at' => '2026-10-24T11:46:00+01:00']);
        $this->actingAs(User::factory()->create())->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $cart)
            ->assertUnprocessable()->assertJsonValidationErrors('arrival_at');
        $this->assertDatabaseCount('orders', 0);
        $this->travelBack();
    }

    public function test_immediate_order_cannot_bypass_closing_hours(): void
    {
        [$restaurant, $item] = $this->saturday();
        $cart = $this->cart($item);
        unset($cart['arrival_at']);
        $this->travelTo(Carbon::parse('2026-10-24T19:01:00+00:00'));
        $this->actingAs(User::factory()->create())->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $cart)->assertUnprocessable();
        $this->assertDatabaseCount('order_quotes', 0);
        $this->travelBack();
    }

    public function test_confirmation_rechecks_opening_hours_for_immediate_service(): void
    {
        [$restaurant, $item] = $this->saturday();
        $this->travelTo(Carbon::parse('2026-10-24T18:59:00+00:00'));
        $cart = $this->cart($item);
        unset($cart['arrival_at']);
        $quote = $this->actingAs(User::factory()->create())->postJson('/api/v1/restaurants/'.$restaurant->id.'/order-quotes', $cart)->assertCreated();
        $this->travel(2)->minutes();
        $this->postJson('/api/v1/restaurants/'.$restaurant->id.'/orders', ['quote_id' => $quote->json('data.id'), 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->travelBack();
    }
}
