<?php

// Prise en main : Yedidia ; tests locaux sans e-mail, opérateur ou prestataire de paiement.

namespace Tests\Feature;

use App\Enums\RestaurantRole;
use App\Models\RestaurantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class AccountCredentialsTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_guests_cannot_change_credentials(): void
    {
        $this->patchJson('/api/v1/me/credentials', [])->assertUnauthorized();
    }

    public function test_current_password_is_required_and_no_other_account_can_be_targeted(): void
    {
        $owner = User::factory()->create(['password' => 'AncienSecret123']);
        $other = User::factory()->create();
        $otherEmail = $other->email;
        $this->actingAs($owner)->patchJson('/api/v1/me/credentials', [
            'current_password' => 'MauvaisSecret123',
            'email' => 'nouveau@example.test',
            'user_id' => $other->id,
        ])->assertUnprocessable();
        $this->assertSame($otherEmail, $other->fresh()->email);
        $this->assertSame($owner->email, $owner->fresh()->email);
    }

    public function test_changes_preserve_restaurant_roles_and_end_the_current_session(): void
    {
        [$restaurant] = $this->restaurantWithMenu();
        $user = $this->employee($restaurant, RestaurantRole::Administrator);
        $user->password = 'AncienSecret123';
        $user->save();
        $this->actingAs($user)->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'email' => '  NOUVEAU@example.test  ',
            'password' => 'NouveauSecret123',
            'password_confirmation' => 'NouveauSecret123',
            'role' => 'platform_admin',
        ])->assertOk()->assertJsonMissingPath('password')->assertJsonMissingPath('data.password');
        $this->assertGuest();
        $this->assertSame('nouveau@example.test', $user->fresh()->email);
        $this->assertTrue(Hash::check('NouveauSecret123', $user->fresh()->password));
        $this->assertFalse(Hash::check('AncienSecret123', $user->fresh()->password));
        $this->assertSame(RestaurantRole::Administrator, RestaurantMembership::where('user_id', $user->id)->sole()->role);
        $this->assertDatabaseCount('platform_grants', 0);
    }

    public function test_used_email_and_weak_password_are_rejected_without_changes(): void
    {
        $user = User::factory()->create(['password' => 'AncienSecret123']);
        $other = User::factory()->create();
        $this->actingAs($user)->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'email' => $other->email,
        ])->assertUnprocessable();
        $this->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'password' => 'sansmajuscule123',
            'password_confirmation' => 'sansmajuscule123',
        ])->assertUnprocessable();
        $this->assertTrue(Hash::check('AncienSecret123', $user->fresh()->password));
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_email_only_change_preserves_the_password_and_revokes_database_sessions(): void
    {
        $user = User::factory()->create(['password' => 'AncienSecret123']);
        $hash = $user->password;
        DB::table('sessions')->insert(['id' => 'autre-session-de-test', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        config(['session.driver' => 'database']);
        $this->actingAs($user)->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'email' => 'identifiant@example.test',
        ])->assertOk();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseMissing('sessions', ['id' => 'autre-session-de-test']);
    }

    public function test_minimum_of_five_characters_applies_to_private_password_changes(): void
    {
        $user = User::factory()->create(['password' => 'AncienSecret123']);
        $this->actingAs($user)->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'password' => 'Ab1c',
            'password_confirmation' => 'Ab1c',
        ])->assertUnprocessable();
        $this->patchJson('/api/v1/me/credentials', [
            'current_password' => 'AncienSecret123',
            'password' => 'Ab12c',
            'password_confirmation' => 'Ab12c',
        ])->assertOk();
        $this->assertTrue(Hash::check('Ab12c', $user->fresh()->password));
    }
}
