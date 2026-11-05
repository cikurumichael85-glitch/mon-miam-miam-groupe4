<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Vérifier le contrat HTTP public et l'absence de données internes dans la réponse.
class ListRestaurantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_restaurants_and_public_fields_are_returned(): void
    {
        $restaurant = Restaurant::factory()->active()->create();
        Restaurant::factory()->create(['name' => 'Restaurant masqué']);
        RestaurantMembership::factory()->for($restaurant)->active()->create();

        $this->getJson('/api/v1/restaurants')->assertOk()->assertExactJson([
            'data' => [[
                'id' => (string) $restaurant->id,
                'name' => $restaurant->name,
                'timezone' => $restaurant->timezone,
                'status' => 'active',
                'delivery_enabled' => false,
            ]],
            'meta' => ['page' => 1, 'per_page' => 20, 'total' => 1],
        ]);
    }

    public function test_pagination_is_stable_and_counts_only_active_restaurants(): void
    {
        $restaurants = Restaurant::factory()->active()->count(3)->create();
        Restaurant::factory()->count(2)->create();

        $this->getJson('/api/v1/restaurants?page=2&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $restaurants[1]->id)
            ->assertJsonPath('meta', ['page' => 2, 'per_page' => 1, 'total' => 3]);
    }

    public function test_empty_directory_returns_a_valid_empty_page(): void
    {
        $this->getJson('/api/v1/restaurants')->assertOk()->assertExactJson([
            'data' => [],
            'meta' => ['page' => 1, 'per_page' => 20, 'total' => 0],
        ]);
    }

    public function test_a_public_filter_cannot_reveal_an_inactive_restaurant(): void
    {
        Restaurant::factory()->create();

        $this->getJson('/api/v1/restaurants?status=inactive')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[DataProvider('invalidPagination')]
    public function test_invalid_pagination_is_rejected(string $query, string $field): void
    {
        $this->getJson('/api/v1/restaurants?'.$query)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['code', 'message', 'fields' => [$field]]]);
    }

    /** @return array<string, array{string, string}> */
    public static function invalidPagination(): array
    {
        return [
            'page nulle' => ['page=0', 'page'],
            'page négative' => ['page=-1', 'page'],
            'page trop grande' => ['page=1000001', 'page'],
            'taille nulle' => ['per_page=0', 'per_page'],
            'taille trop grande' => ['per_page=101', 'per_page'],
            'texte' => ['per_page=abc', 'per_page'],
            'fraction' => ['per_page=1.5', 'per_page'],
            'tableau' => ['per_page%5B%5D=10', 'per_page'],
        ];
    }
}
