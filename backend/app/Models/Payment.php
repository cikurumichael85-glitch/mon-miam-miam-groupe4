<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['*'];

    protected $attributes = ['is_active' => true];

    protected $hidden = ['merchant_fingerprint'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'verified_at' => 'datetime', 'is_active' => 'boolean', 'archived_at' => 'datetime'];
    }
}
