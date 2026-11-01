<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Money
{
    // Limite technique proposée : évite un débordement lors de la multiplication par quantité.
    public const INPUT_PATTERN = '/^\d{1,9}(\.\d{1,2})?$/';

    public static function minor(string $amount): int
    {
        if (! preg_match(self::INPUT_PATTERN, $amount)) {
            throw ValidationException::withMessages(['amount' => 'Montant CDF invalide (9 chiffres et 2 décimales maximum).']);
        }
        $parts = explode('.', $amount);

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function decimal(int $minor): string
    {
        // Les calculs monétaires ne passent jamais par un nombre flottant.
        return intdiv($minor, 100).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }
}
