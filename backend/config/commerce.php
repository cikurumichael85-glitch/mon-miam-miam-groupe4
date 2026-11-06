<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

return [
    // Décisions techniques proposées, documentées et modifiables avant la recette.
    'quote_minutes' => 10,
    'max_lines' => 50,
    'max_quantity' => 99,
    'frontend_url' => env('FRONTEND_URL', 'http://127.0.0.1:5173'),
    // Prestataire choisi par le groupe ; activé séparément après configuration du mode test.
    'payment_provider' => 'e-com-easypay',
];
