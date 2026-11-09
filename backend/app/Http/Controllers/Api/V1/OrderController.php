<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\CashPaymentService;
use App\Services\OrderService;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class OrderController extends Controller
{
    public function __construct(private ApiAccess $access, private OrderService $orders) {}

    public function quote(Request $request, Restaurant $restaurant, QuoteService $quotes): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.config('commerce.max_lines')],
            'items.*' => ['required', 'array:item_id,quantity'],
            'items.*.item_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'between:1,'.config('commerce.max_quantity')],
            'service_mode' => ['required', Rule::in(['on_site', 'delivery'])],
            // Absence d'arrivée sur place : commande immédiate, datée par le serveur.
            'arrival_at' => ['sometimes', 'prohibited_if:service_mode,delivery', 'date_format:Y-m-d\TH:i:sP', 'after:now', 'before:+7 days'],
            'delivery' => ['required_if:service_mode,delivery', 'prohibited_if:service_mode,on_site', 'array:address,phone,instructions'],
            'delivery.address' => ['required_with:delivery', 'string', 'max:1000'],
            'delivery.phone' => ['required_with:delivery', 'string', 'max:32'],
            'delivery.instructions' => ['nullable', 'string', 'max:500'],
            'points' => ['sometimes', 'integer', 'between:0,1000000'],
        ], [
            'arrival_at.after' => 'L’heure planifiée est déjà passée. Choisissez Commander maintenant ou une heure future.',
            'arrival_at.before' => 'Choisissez une arrivée dans les sept prochains jours.',
            'arrival_at.date_format' => 'Le format de l’heure d’arrivée est invalide.',
        ]);

        return response()->json(['data' => $quotes->create($restaurant, $request->user()->id, $data)], 201);
    }

    public function store(Request $request, Restaurant $restaurant, CashPaymentService $cash): JsonResponse
    {
        $data = $request->validate([
            'quote_id' => ['required', 'uuid'],
            'idempotency_key' => ['required', 'uuid'],
            'payment_method' => ['sometimes', Rule::in(['cash', 'easypay'])],
        ]);
        $order = DB::transaction(function () use ($request, $restaurant, $data, $cash): Order {
            $record = $this->orders->confirm($restaurant, $request->user()->id, $data['quote_id'], $data['idempotency_key']);
            if (($data['payment_method'] ?? null) === 'cash' && $record->total !== '0.00') {
                $cash->select($record, $request->user()->id);
            }

            return $record;
        }, 3);

        return response()->json([
            'data' => $order,
            'payment' => Payment::where('order_id', $order->id)->where('is_active', true)->first(['id', 'state', 'verified_at', 'mode']),
        ], $order->wasRecentlyCreated ? 201 : 200);
    }

    public function mine(Request $request): JsonResponse
    {
        $this->pagination($request);

        return response()->json(Order::where('customer_id', $request->user()->id)->latest('id')->paginate(25));
    }

    public function ownDetail(Request $request, int $order): JsonResponse
    {
        $record = Order::where('customer_id', $request->user()->id)->findOrFail($order);

        return $this->detail($record);
    }

    public function restaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);
        $this->pagination($request);

        return response()->json(Order::where('restaurant_id', $restaurant->id)->latest('id')->paginate(25));
    }

    public function staffDetail(Request $request, Restaurant $restaurant, int $order): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);

        return $this->detail(Order::where('restaurant_id', $restaurant->id)->findOrFail($order), true);
    }

    public function transition(Request $request, Restaurant $restaurant, int $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled', 'rejected'])],
            'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required_if:status,cancelled,rejected', 'nullable', 'string', 'max:1000'],
        ]);
        $management = in_array($data['status'], ['cancelled', 'rejected'], true);
        $this->access->staff($request->user(), $restaurant, $management);

        return response()->json(['data' => $this->orders->transition($restaurant, $order, $request->user()->id, $data)]);
    }

    private function detail(Order $order, bool $staff = false): JsonResponse
    {
        $payment = Payment::where('order_id', $order->id)->where('is_active', true)->first(['id', 'state', 'verified_at', 'mode']);

        return response()->json([
            'data' => $order,
            'lines' => DB::table('order_lines')->where('order_id', $order->id)->get(),
            'events' => DB::table('order_events')->where('order_id', $order->id)->orderBy('version')->get(),
            'payment' => $payment,
            'cash_receipt' => $staff && $payment?->mode === 'cash'
                ? DB::table('audit_events')->leftJoin('users', 'users.id', '=', 'audit_events.actor_id')
                    ->where('action', 'payment.cash.received')->where('target', (string) $payment->id)
                    ->where('audit_events.restaurant_id', $order->restaurant_id)
                    ->first(['users.name as actor_name', 'audit_events.occurred_at'])
                : null,
            'payment_attempts' => Payment::where('order_id', $order->id)->orderBy('id')->get(['id', 'mode', 'state', 'is_active', 'created_at', 'archived_at']),
        ]);
    }

    private function pagination(Request $request): void
    {
        $request->validate(['page' => ['sometimes', 'integer', 'between:1,1000000']]);
    }
}
