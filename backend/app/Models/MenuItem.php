<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    // Seuls les services valident et affectent les attributs autorisés.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean',
            'is_daily' => 'boolean',
            'archived_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
