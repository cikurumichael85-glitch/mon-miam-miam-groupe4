<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimReply extends Model
{
    // Seuls les services valident et affectent les attributs autorisés.
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }
}
