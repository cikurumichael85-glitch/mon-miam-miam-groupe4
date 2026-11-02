<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Models\RestaurantMembership;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Vérifier que la base protège l'intégrité même en dehors d'un futur contrôleur HTTP.
class RestaurantMembershipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_membership_for_the_same_user_and_restaurant_is_rejected(): void
    {
        $membership = RestaurantMembership::factory()->create();

        $this->expectException(QueryException::class);

        RestaurantMembership::factory()->create([
            'restaurant_id' => $membership->restaurant_id,
            'user_id' => $membership->user_id,
        ]);
    }

    public function test_unknown_restaurant_cannot_be_referenced(): void
    {
        $this->expectException(QueryException::class);

        RestaurantMembership::factory()->create(['restaurant_id' => 999999]);
    }

    public function test_deletion_of_a_restaurant_with_memberships_is_restricted(): void
    {
        $membership = RestaurantMembership::factory()->create();

        $this->expectException(QueryException::class);

        $membership->restaurant->delete();
    }

    public function test_arbitrary_role_is_rejected_by_the_database(): void
    {
        $membership = RestaurantMembership::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('restaurant_memberships')->where('id', $membership->id)
            ->update(['role' => 'platform_admin']);
    }

    public function test_privileged_fields_cannot_be_mass_assigned(): void
    {
        $membership = new RestaurantMembership;

        $this->expectException(MassAssignmentException::class);

        $membership->fill(['role' => 'restaurant_admin', 'active' => true]);
    }
}
