<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Enums;

// Statut global du compte : une suspension bloque ses accès dans tous les restaurants.
enum AccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
}
