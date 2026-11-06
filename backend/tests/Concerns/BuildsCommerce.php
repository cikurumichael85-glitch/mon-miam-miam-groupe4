<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Concerns;

use App\Enums\RestaurantRole;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Models\User;
use App\Services\OrderService;
use App\Services\QuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait BuildsCommerce
{
    protected function restaurantWithMenu(): array
    {
        $restaurant = Restaurant::factory()->active()->create(['timezone' => 'Africa/Kinshasa']);
        for ($day = 1; $day <= 7; $day++) {
            DB::table('restaurant_hours')->insert(['restaurant_id' => $restaurant->id, 'weekday' => $day, 'opens_at' => '00:00', 'closes_at' => '23:59']);
        }
        $category = new MenuCategory;
        $category->forceFill(['restaurant_id' => $restaurant->id, 'name' => 'Plats']);
        $category->save();
        $item = new MenuItem;
        $item->forceFill(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Plat de test', 'price' => '2500.50', 'available' => true]);
        $item->save();

        return [$restaurant, $item];
    }

    protected function cart(MenuItem $item): array
    {
        return [
            'items' => [['item_id' => $item->id, 'quantity' => 2]],
            'service_mode' => 'on_site',
            'arrival_at' => now()->addDay()->setTime(12, 0)->toIso8601String(),
        ];
    }

    protected function place(User $user, Restaurant $restaurant, MenuItem $item, array $extra = []): Order
    {
        $quote = app(QuoteService::class)->create($restaurant, $user->id, array_replace($this->cart($item), $extra));

        return app(OrderService::class)->confirm($restaurant, $user->id, $quote->id, (string) Str::uuid());
    }

    protected function employee(Restaurant $restaurant, RestaurantRole $role = RestaurantRole::Employee): User
    {
        $user = User::factory()->create();
        RestaurantMembership::factory()->active()->create(['restaurant_id' => $restaurant->id, 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    protected function program(Restaurant $restaurant, User $manager): int
    {
        return DB::table('loyalty_programs')->insertGetId([
            'scope_key' => 'restaurant:'.$restaurant->id,
            'restaurant_id' => $restaurant->id,
            'active' => true,
            // Valeurs de test, pas un barème activé dans la base applicative.
            'rules' => json_encode(['earn_amount' => '1000.00', 'redeem_points' => 5, 'redeem_value' => '1000.00', 'referral_points' => 3, 'expiry_days' => 30, 'require_paid' => false]),
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
