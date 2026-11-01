<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderQuote extends Model
{
    // Seuls les services valident et affectent les attributs autorisés.
    protected $guarded = ['*'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
