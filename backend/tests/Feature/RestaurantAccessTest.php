<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\RestaurantRole;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Models\User;
use App\Services\RestaurantAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Tester les refus entre restaurants et l'effet immédiat des révocations en base.
class RestaurantAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Restaurant $restaurant;

    private RestaurantMembership $membership;

    private RestaurantAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->restaurant = Restaurant::factory()->active()->create();
        $this->membership = RestaurantMembership::factory()
            ->for($this->user)
            ->for($this->restaurant)
            ->active()
            ->create();
        $this->access = new RestaurantAccess;
    }

    public function test_active_employee_can_access_their_own_restaurant(): void
    {
        $this->assertTrue($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_membership_in_a_does_not_grant_access_to_b(): void
    {
        $otherRestaurant = Restaurant::factory()->active()->create();

        $this->assertFalse($this->access->allows(
            $this->user,
            $otherRestaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_employee_cannot_use_a_manager_permission(): void
    {
        $this->assertFalse($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Manager, RestaurantRole::Administrator],
        ));
    }

    public function test_membership_revocation_is_effective_without_reloading_the_user(): void
    {
        $this->user->load('memberships');
        $this->membership->active = false;
        $this->membership->save();

        $this->assertFalse($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_suspended_account_is_refused_even_with_an_active_membership(): void
    {
        $freshUser = $this->user->fresh();
        $freshUser->status = AccountStatus::Suspended;
        $freshUser->save();

        $this->assertFalse($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_inactive_restaurant_is_refused_even_with_an_active_membership(): void
    {
        $freshRestaurant = $this->restaurant->fresh();
        $freshRestaurant->status = RestaurantStatus::Inactive;
        $freshRestaurant->save();

        $this->assertFalse($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_empty_permissions_do_not_allow_access(): void
    {
        $this->assertFalse($this->access->allows($this->user, $this->restaurant, []));
    }

    public function test_user_without_membership_is_refused(): void
    {
        $otherUser = User::factory()->create();

        $this->assertFalse($this->access->allows(
            $otherUser,
            $this->restaurant,
            [RestaurantRole::Employee],
        ));
    }

    public function test_roles_are_independent_for_the_same_user_in_two_restaurants(): void
    {
        $otherRestaurant = Restaurant::factory()->active()->create();
        RestaurantMembership::factory()->for($this->user)->for($otherRestaurant)
            ->active()->create(['role' => RestaurantRole::Manager]);

        $this->assertFalse($this->access->allows(
            $this->user,
            $this->restaurant,
            [RestaurantRole::Manager],
        ));
        $this->assertTrue($this->access->allows(
            $this->user,
            $otherRestaurant,
            [RestaurantRole::Manager],
        ));
    }
}
