<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Seuls les services valident et affectent les attributs autorisés.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'arrival_at' => 'datetime',
            'delivery_snapshot' => 'array',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'completed_at' => 'datetime',
            'version' => 'integer',
            'points_used' => 'integer',
        ];
    }
}
