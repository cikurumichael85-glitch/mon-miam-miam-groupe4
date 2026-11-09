<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\CashPaymentService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CashPaymentController extends Controller
{
    public function select(Request $request, int $order, CashPaymentService $cash): JsonResponse
    {
        $record = Order::where('customer_id', $request->user()->id)->findOrFail($order);

        return response()->json(['data' => $cash->select($record, $request->user()->id)]);
    }

    public function receive(Request $request, Restaurant $restaurant, int $order, ApiAccess $access, CashPaymentService $cash): JsonResponse
    {
        $access->staff($request->user(), $restaurant);
        $data = $request->validate([
            'amount' => ['required', 'string', 'regex:'.Money::INPUT_PATTERN],
            'received' => ['required', 'accepted'],
        ]);
        $record = Order::where('restaurant_id', $restaurant->id)->findOrFail($order);

        return response()->json(['data' => $cash->receive($record, $request->user()->id, $data['amount'])]);
    }
}
