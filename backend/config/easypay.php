<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

return [
    'enabled' => (bool) env('EASYPAY_ENABLED', false),
    'mode' => env('EASYPAY_MODE', 'sandbox'),
    // JSON privé : {"identifiant_restaurant":{"cid":"...","publishable_key":"..."}}.
    // Chaque restaurant encaisse sur son propre compte marchand, sans reversement inventé.
    // confirmed_networks facultatif : codes réellement confirmés pour ce marchand en CDF.
    'merchants' => json_decode(env('EASYPAY_MERCHANTS', '{}'), true),
    'return_base' => env('FRONTEND_URL', 'http://127.0.0.1:5173'),
];
