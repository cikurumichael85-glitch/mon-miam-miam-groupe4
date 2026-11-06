<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'application' => 'Mon Miam Miam',
        'status' => 'socle',
        'currency' => 'CDF',
    ]);
});
