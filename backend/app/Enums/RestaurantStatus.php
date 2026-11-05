<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Enums;

// Publication du restaurant ; l'ouverture selon les horaires sera gérée séparément.
enum RestaurantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
