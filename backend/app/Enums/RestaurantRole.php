<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Enums;

// Rôle porté par un rattachement précis, sans pouvoir global sur la plateforme.
enum RestaurantRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Administrator = 'restaurant_admin';
}
