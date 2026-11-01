<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use App\Enums\RestaurantRole;
use Database\Factories\RestaurantMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Lie un compte à un restaurant : unicité et clés étrangères sont imposées par la migration.
class RestaurantMembership extends Model
{
    /** @use HasFactory<RestaurantMembershipFactory> */
    use HasFactory;

    // Les droits se définissent explicitement, jamais depuis un payload client.
    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => RestaurantRole::class,
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
