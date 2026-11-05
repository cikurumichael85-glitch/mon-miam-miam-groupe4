<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OrderService
{
    public function __construct(private QuoteService $quotes, private LoyaltyService $loyalty) {}

    public function confirm(Restaurant $restaurant, int $user, string $quoteId, string $key): Order
    {
        return DB::transaction(function () use ($restaurant, $user, $quoteId, $key): Order {
            // Toujours le même ordre de verrous : restaurant, client, devis, articles, programme.
            $restaurant = Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $customer = User::lockForUpdate()->findOrFail($user);
            abort_unless($customer->status === AccountStatus::Active, 403, 'Compte désactivé.');
            $existing = Order::where('customer_id', $user)->where('idempotency_key', $key)->first();
            if ($existing) {
                abort_if($existing->quote_id !== $quoteId || $existing->restaurant_id !== $restaurant->id, 409, 'Clé déjà utilisée pour une autre commande.');

                return $existing;
            }
            $quote = OrderQuote::where('restaurant_id', $restaurant->id)->where('customer_id', $user)->lockForUpdate()->findOrFail($quoteId);
            abort_if(Order::where('quote_id', $quoteId)->exists(), 409, 'Devis déjà utilisé.');
            abort_if($quote->expires_at->isPast(), 409, 'Devis expiré.');
            try {
                $fresh = $this->quotes->calculate($restaurant, $user, $quote->payload['input']);
            } catch (ValidationException) {
                abort(409, 'Les points disponibles ont changé. Demandez un nouveau devis.');
            }
            abort_if($fresh !== $quote->payload, 409, 'Le prix, le menu ou les règles ont changé. Demandez un nouveau devis.');
            $order = $this->persist($quote, $key);
            $this->loyalty->redeem($order);
            $this->event($order, $user, null, null);

            return $order;
        }, 3);
    }

    private function persist(OrderQuote $quote, string $key): Order
    {
        $payload = $quote->payload;
        $order = new Order;
        $order->forceFill([
            'restaurant_id' => $quote->restaurant_id,
            'customer_id' => $quote->customer_id,
            'quote_id' => $quote->id,
            'idempotency_key' => $key,
            'reference' => 'MMM-'.Str::uuid(),
            'service_mode' => $payload['input']['service_mode'],
            'arrival_at' => isset($payload['input']['arrival_at']) ? Carbon::parse($payload['input']['arrival_at'])->utc() : null,
            'delivery_snapshot' => $payload['input']['delivery'] ?? null,
            'subtotal' => $payload['subtotal'],
            'discount' => $payload['discount'],
            'delivery_fee' => $payload['delivery_fee'],
            'total' => $payload['total'],
            'currency' => 'CDF',
            'status' => 'pending',
            'payment_state' => 'unrecorded',
            'version' => 1,
            'loyalty_program_id' => $payload['program_id'],
            'points_used' => $payload['points'],
        ]);
        $order->save();
        foreach ($payload['lines'] as $line) {
            DB::table('order_lines')->insert([
                'restaurant_id' => $order->restaurant_id,
                'order_id' => $order->id,
                'menu_item_id' => $line['item_id'],
                'name' => $line['name'],
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
                'total' => Money::decimal($line['minor_total']),
            ]);
        }

        return $order;
    }

    public function transition(Restaurant $restaurant, int $id, int $actor, array $input): Order
    {
        return DB::transaction(function () use ($restaurant, $id, $actor, $input): Order {
            Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $order = Order::where('restaurant_id', $restaurant->id)->lockForUpdate()->findOrFail($id);
            abort_if($order->version !== (int) $input['expected_version'], 409, 'La commande a changé.');
            $from = $order->status;
            $this->assertTransition($order, $input['status']);
            $order->status = $input['status'];
            $order->version++;
            $order->completed_at = $order->status === 'completed' ? now() : null;
            $order->save();
            if (in_array($order->status, ['cancelled', 'rejected'], true)) {
                $this->loyalty->restore($order);
            }
            $this->loyalty->reward($order);
            $this->event($order, $actor, $from, $input['reason'] ?? null);

            return $order;
        }, 3);
    }

    private function assertTransition(Order $order, string $target): void
    {
        $next = [
            'pending' => ['accepted', 'rejected', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready'],
            'ready' => [$order->service_mode === 'delivery' ? 'out_for_delivery' : 'completed'],
            'out_for_delivery' => ['completed'],
        ];
        abort_unless(in_array($target, $next[$order->status] ?? [], true), 409, 'Transition impossible.');
        abort_if(in_array($target, ['cancelled', 'rejected'], true) && $order->payment_state === 'paid', 409, 'Le remboursement doit être traité avant cette annulation.');
        $paymentPending = DB::table('payments')->where('order_id', $order->id)->where('is_active', true)
            ->whereIn('state', ['initializing', 'pending', 'unknown', 'review'])->exists();
        abort_if(in_array($target, ['cancelled', 'rejected'], true) && $paymentPending, 409, 'Vérifiez le paiement en cours avant l’annulation.');
    }

    private function event(Order $order, int $actor, ?string $from, ?string $reason): void
    {
        DB::table('order_events')->insert([
            'order_id' => $order->id,
            'actor_id' => $actor,
            'from_status' => $from,
            'to_status' => $order->status,
            'reason' => $reason,
            'version' => $order->version,
            'occurred_at' => now(),
        ]);
    }
}
