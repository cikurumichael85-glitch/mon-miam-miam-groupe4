<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class ClaimsWorkflowTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_only_an_approved_reply_reaches_the_customer(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $order = $this->place($customer, $restaurant, $item);
        $claim = $this->actingAs($customer)->postJson('/api/v1/me/orders/'.$order->id.'/claims', ['subject' => 'Test', 'message' => 'Réclamation fictive'])->assertCreated()->json('data.id');
        $employee = $this->employee($restaurant);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/claims/'.$claim;
        $reply = $this->actingAs($employee)->postJson($url.'/replies', ['body' => 'Réponse à approuver'])->assertCreated()->json('data.id');
        $this->actingAs($customer)->getJson('/api/v1/me/claims/'.$claim)->assertJsonCount(0, 'replies');
        $this->actingAs($employee)->postJson($url.'/replies/'.$reply.'/review', ['decision' => 'approved'])->assertForbidden();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $this->actingAs($manager)->postJson($url.'/replies/'.$reply.'/review', ['decision' => 'approved'])->assertOk();
        $this->actingAs($customer)->getJson('/api/v1/me/claims/'.$claim)->assertJsonCount(1, 'replies');
    }

    public function test_a_manager_cannot_approve_their_own_reply(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $order = $this->place($customer, $restaurant, $item);
        $claim = $this->actingAs($customer)->postJson('/api/v1/me/orders/'.$order->id.'/claims', ['subject' => 'Test', 'message' => 'Test'])->json('data.id');
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $url = '/api/v1/restaurants/'.$restaurant->id.'/claims/'.$claim;
        $reply = $this->actingAs($manager)->postJson($url.'/replies', ['body' => 'Réponse du gérant'])->assertCreated()->json('data.id');
        $this->postJson($url.'/replies/'.$reply.'/review', ['decision' => 'approved'])->assertForbidden();
    }

    public function test_rejected_reply_stays_private_and_another_reply_can_resolve_the_claim(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $customer = User::factory()->create();
        $server = $this->employee($restaurant);
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $order = $this->place($customer, $restaurant, $item);
        $claim = $this->actingAs($customer)->postJson('/api/v1/me/orders/'.$order->id.'/claims', ['subject' => 'Test', 'message' => 'Demande fictive'])->assertCreated()->json('data.id');
        $root = '/api/v1/restaurants/'.$restaurant->id.'/claims/'.$claim;
        $first = $this->actingAs($server)->postJson($root.'/replies', ['body' => 'Brouillon refusé'])->assertCreated()->json('data.id');
        $this->actingAs($manager)->postJson($root.'/replies/'.$first.'/review', ['decision' => 'rejected'])->assertOk();
        $this->actingAs($customer)->getJson('/api/v1/me/claims/'.$claim)->assertOk()->assertJsonPath('data.status', 'open')->assertJsonCount(0, 'replies');
        $second = $this->actingAs($server)->postJson($root.'/replies', ['body' => 'Réponse validée'])->assertCreated()->json('data.id');
        $this->actingAs($manager)->postJson($root.'/replies/'.$second.'/review', ['decision' => 'approved'])->assertOk();
        $this->actingAs($customer)->getJson('/api/v1/me/claims/'.$claim)->assertOk()->assertJsonPath('data.status', 'resolved')
            ->assertJsonCount(1, 'replies')->assertJsonPath('replies.0.body', 'Réponse validée');
        $this->actingAs($server)->postJson($root.'/replies', ['body' => 'Après résolution'])->assertConflict();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/me/claims/'.$claim)->assertNotFound();
    }
}
