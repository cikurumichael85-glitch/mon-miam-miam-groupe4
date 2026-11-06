<?php

// Prise en main : Yedidia ; politique commune à l'inscription et aux changements privés.

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class AccountPassword
{
    public static function rules(): array
    {
        return [
            'required',
            'string',
            'confirmed',
            Password::min(5)->mixedCase()->numbers(),
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail('Mot de passe trop long : 72 octets maximum.');
                }
            },
        ];
    }
}
