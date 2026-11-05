<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReviewController extends Controller
{
    public function index(Restaurant $restaurant): JsonResponse
    {
        app(ApiAccess::class)->active($restaurant);

        return response()->json(DB::table('reviews')->where('restaurant_id', $restaurant->id)->where('visible', true)
            ->select(['id', 'rating', 'comment', 'created_at'])->orderByDesc('id')->paginate(25));
    }

    public function store(Request $request, int $order): JsonResponse
    {
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $order, $data): void {
            $record = Order::where('customer_id', $request->user()->id)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'completed', 422, 'Un avis exige une commande servie ou livrée.');
            abort_if(DB::table('reviews')->where('order_id', $order)->exists(), 409, 'Un avis existe déjà.');
            DB::table('reviews')->insert($data + [
                'order_id' => $order,
                'restaurant_id' => $record->restaurant_id,
                'user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Avis enregistré.'], 201);
    }

    public function hide(Request $request, Restaurant $restaurant, int $review): JsonResponse
    {
        app(ApiAccess::class)->staff($request->user(), $restaurant, true);
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $record = DB::table('reviews')->where('restaurant_id', $restaurant->id)->where('id', $review)->first();
        abort_unless($record, 404);
        DB::table('reviews')->where('id', $review)->update(['visible' => false, 'updated_at' => now()]);
        Audit::record($request->user()->id, $restaurant->id, 'review.hidden', (string) $review);

        return response()->json(['message' => 'Avis masqué.']);
    }
}
