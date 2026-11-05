<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class CommunityPrivacyTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_a_quiz_hides_its_answer_and_limits_each_user_to_one_attempt(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $manager = $this->employee($restaurant, RestaurantRole::Manager);
        $payload = [
            'kind' => 'quiz',
            'title' => 'Quiz fictif',
            'description' => 'Démonstration sans récompense financière.',
            'starts_at' => now()->subHour()->toIso8601String(),
            'ends_at' => now()->addHour()->toIso8601String(),
            'active' => true,
            'rules' => ['question' => 'Choix ?', 'choices' => ['A', 'B'], 'answer_index' => 1, 'reward_points' => 0, 'max_winners' => 1],
        ];
        $base = '/api/v1/restaurants/'.$restaurant->id.'/campaigns';
        $id = $this->actingAs($manager)->postJson($base, $payload)->assertCreated()->assertJsonMissingPath('data.rules.answer_index')->json('data.id');
        $this->actingAs(User::factory()->create())->postJson($base.'/'.$id.'/participate', ['answer_index' => 0])->assertOk()->assertJsonPath('data.won', false);
        $this->postJson($base.'/'.$id.'/participate', ['answer_index' => 1])->assertOk()->assertJsonPath('data.won', false);
        $this->assertDatabaseCount('campaign_entries', 1);
    }

    public function test_reviews_require_a_completed_order_and_are_unique(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        $user = User::factory()->create();
        $order = $this->place($user, $restaurant, $item);
        $url = '/api/v1/me/orders/'.$order->id.'/review';
        $data = ['rating' => 5, 'comment' => 'Avis fictif'];
        $this->actingAs($user)->postJson($url, $data)->assertUnprocessable();
        $order->status = 'completed';
        $order->save();
        $this->postJson($url, $data)->assertCreated();
        $this->postJson($url, $data)->assertConflict();
    }

    public function test_anonymization_is_blocked_without_a_retention_policy(): void
    {
        $user = User::factory()->create();
        $platform = User::factory()->create();
        DB::table('platform_grants')->insert(['user_id' => $platform->id, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('privacy_requests')->insertGetId(['user_id' => $user->id, 'kind' => 'erasure', 'status' => 'in_review', 'created_at' => now()]);
        config(['privacy.retention_days' => null]);
        $this->actingAs($platform)->postJson('/api/v1/platform/privacy-requests/'.$id.'/anonymize', ['password' => 'password', 'confirm' => true])->assertConflict();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => $user->email]);
    }

    public function test_other_restaurant_staff_cannot_read_orders(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $order = $this->place(User::factory()->create(), $restaurant, $item);
        $staff = $this->employee($other);
        $this->actingAs($staff)->getJson('/api/v1/restaurants/'.$restaurant->id.'/orders/'.$order->id)->assertForbidden();
        $this->getJson('/api/v1/restaurants/'.$other->id.'/orders/'.$order->id)->assertNotFound();
    }
}
