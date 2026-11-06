<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_never_accepts_a_privileged_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Client fictif',
            'email' => 'client@example.test',
            'password' => 'MotDeTest1234',
            'password_confirmation' => 'MotDeTest1234',
            'phone' => '000000000',
            'role' => 'platform_admin',
        ])->assertCreated()->assertJsonMissingPath('data.password');
        $this->assertDatabaseCount('platform_grants', 0);
        $this->assertDatabaseCount('restaurant_memberships', 0);
    }

    public function test_private_routes_reject_guests_and_suspended_accounts(): void
    {
        $this->getJson('/api/v1/me/orders')->assertUnauthorized();
        $user = User::factory()->create(['status' => AccountStatus::Suspended]);
        $this->actingAs($user)->getJson('/api/v1/me/orders')->assertForbidden();
    }

    public function test_mutations_require_csrf_outside_the_test_bypass(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->postJson('/api/v1/auth/login', ['email' => 'test@example.test', 'password' => 'wrong'])->assertStatus(419);
    }
}
