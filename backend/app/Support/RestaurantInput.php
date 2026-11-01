<?php

// Prise en main : Ryan ; les deux parcours de création partagent les mêmes règles.

namespace App\Support;

final class RestaurantInput
{
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:2000'],
            'timezone' => ['required', 'timezone:all'],
            'delivery_enabled' => ['required', 'boolean'],
            'delivery_fee' => ['required_if:delivery_enabled,true,1', 'nullable', 'string', 'regex:'.Money::INPUT_PATTERN],
        ];
    }
}
