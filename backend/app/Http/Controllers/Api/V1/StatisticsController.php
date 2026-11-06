<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class StatisticsController extends Controller
{
    public function __construct(private ApiAccess $access) {}

    public function restaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);
        $period = $request->validate(['period' => ['required', Rule::in(['day', 'week', 'month'])]])['period'];
        if ($period !== 'week') {
            $this->access->staff($request->user(), $restaurant, true);
        }

        return $this->summary(Order::where('restaurant_id', $restaurant->id), $period, $restaurant->timezone);
    }

    public function platform(Request $request): JsonResponse
    {
        $this->access->platform($request->user());
        $data = $request->validate(['period' => ['required', Rule::in(['day', 'week', 'month'])]]);

        return $this->summary(Order::query(), $data['period'], 'UTC');
    }

    private function start(string $period, string $timezone): Carbon
    {
        return match ($period) {
            'day' => now($timezone)->startOfDay(),
            'week' => now($timezone)->startOfWeek(),
            default => now($timezone)->startOfMonth(),
        };
    }

    private function summary(Builder $query, string $period, string $timezone): JsonResponse
    {
        $query->where('created_at', '>=', $this->start($period, $timezone)->utc())->where('created_at', '<=', now());
        $completed = (clone $query)->where('status', 'completed');
        $paid = (clone $completed)->where('payment_state', 'paid');

        return response()->json(['data' => [
            'period' => $period,
            'timezone' => $timezone,
            'orders' => (clone $query)->count(),
            'completed_orders' => (clone $completed)->count(),
            'completed_total' => (string) (clone $completed)->sum('total'),
            'paid_completed_total' => (string) $paid->sum('total'),
            'points_used' => (int) (clone $completed)->sum('points_used'),
            'points_awarded' => (int) DB::table('loyalty_entries')->whereIn('order_id', (clone $completed)->select('orders.id'))
                ->where('kind', 'earn')->where('delta', '>', 0)->sum('delta'),
            'referrals_rewarded' => DB::table('referrals')->whereIn('qualifying_order_id', (clone $completed)->select('orders.id'))->count(),
            'currency' => 'CDF',
            'generated_at' => now()->toIso8601String(),
        ]]);
    }

    public function ranking(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->active($restaurant);
        $data = $request->validate(['period' => ['required', Rule::in(['day', 'week', 'month'])]]);
        $rows = Order::query()->join('users', 'users.id', '=', 'orders.customer_id')
            ->where('orders.restaurant_id', $restaurant->id)->where('orders.status', 'completed')
            ->where('orders.completed_at', '>=', $this->start($data['period'], $restaurant->timezone)->utc())
            ->where('users.ranking_opt_in', true)->where('users.status', 'active')
            ->selectRaw('users.public_alias, COUNT(*) AS orders_count')
            ->groupBy('users.id', 'users.public_alias')->orderByDesc('orders_count')->orderBy('users.id')->limit(10)->get();

        return response()->json(['data' => $rows]);
    }
}
