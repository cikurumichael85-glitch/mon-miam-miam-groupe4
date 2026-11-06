<?php

// Prise en main : Ryan ; relecture Yedidia pour les informations de paiement.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\EasyPayGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class CheckoutOptionsController extends Controller
{
    public function __invoke(Restaurant $restaurant, ApiAccess $access, EasyPayGateway $gateway): JsonResponse
    {
        $access->active($restaurant);

        return response()->json([
            'restaurant_id' => (string) $restaurant->id,
            'timezone' => $restaurant->timezone,
            'delivery_enabled' => $restaurant->delivery_enabled && $restaurant->delivery_fee !== null,
            'delivery_fee' => $restaurant->delivery_fee,
            'hours' => DB::table('restaurant_hours')->where('restaurant_id', $restaurant->id)
                ->orderBy('weekday')->orderBy('opens_at')->get(['weekday', 'opens_at', 'closes_at']),
            'payment' => $gateway->publicStatus($restaurant->id),
        ])->header('Cache-Control', 'no-store');
    }
}
