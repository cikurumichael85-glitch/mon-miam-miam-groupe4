<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace Tests\Feature;

use App\Models\MenuItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCommerce;
use Tests\TestCase;

class MenuIntegrityTest extends TestCase
{
    use BuildsCommerce, RefreshDatabase;

    public function test_the_database_rejects_a_category_from_another_restaurant(): void
    {
        [$restaurant, $item] = $this->restaurantWithMenu();
        [$other] = $this->restaurantWithMenu();
        $foreign = new MenuItem;
        $foreign->forceFill(['restaurant_id' => $other->id, 'category_id' => $item->category_id, 'name' => 'Invalide', 'price' => '1.00']);
        $this->expectException(QueryException::class);
        $foreign->save();
    }
}
