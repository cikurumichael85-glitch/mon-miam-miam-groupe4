<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Services;

use App\Enums\AccountStatus;
use App\Models\MenuItem;
use App\Models\OrderQuote;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QuoteService
{
    public function __construct(private LoyaltyService $loyalty) {}

    public function create(Restaurant $restaurant, int $user, array $input): OrderQuote
    {
        return DB::transaction(function () use ($restaurant, $user, $input): OrderQuote {
            $restaurant = Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $customer = User::lockForUpdate()->findOrFail($user);
            abort_unless($customer->status === AccountStatus::Active, 403, 'Compte désactivé.');
            $quote = new OrderQuote;
            $quote->id = (string) Str::uuid();
            $quote->restaurant_id = $restaurant->id;
            $quote->customer_id = $user;
            $quote->payload = $this->calculate($restaurant, $user, $input);
            $quote->expires_at = now()->addMinutes(config('commerce.quote_minutes'));
            $quote->save();

            return $quote;
        });
    }

    public function calculate(Restaurant $restaurant, int $user, array $input): array
    {
        app(ApiAccess::class)->active($restaurant);
        $this->validateService($restaurant, $input);
        $lines = $this->lines($restaurant->id, $input['items']);
        $subtotal = array_sum(array_column($lines, 'minor_total'));
        abort_if($subtotal > 99999999999, 422, 'Montant de commande trop élevé.');
        $program = $this->loyalty->program($restaurant->id);
        $points = (int) ($input['points'] ?? 0);
        $discount = $this->loyalty->discount($program, $user, $points, $subtotal);
        $fee = $input['service_mode'] === 'delivery' ? Money::minor((string) $restaurant->delivery_fee) : 0;
        abort_if($subtotal - $discount + $fee > 99999999999, 422, 'Montant de commande trop élevé.');

        return [
            'input' => $input,
            'lines' => $lines,
            'settings_version' => $restaurant->settings_version,
            'program_id' => $program?->id,
            'program_version' => $program?->version,
            'points' => $points,
            'subtotal' => Money::decimal($subtotal),
            'discount' => Money::decimal($discount),
            'delivery_fee' => Money::decimal($fee),
            'total' => Money::decimal($subtotal - $discount + $fee),
            'currency' => 'CDF',
        ];
    }

    private function lines(int $restaurant, array $requested): array
    {
        usort($requested, fn (array $left, array $right) => $left['item_id'] <=> $right['item_id']);
        $lines = [];
        foreach ($requested as $line) {
            $item = MenuItem::where('restaurant_id', $restaurant)->whereNull('archived_at')->lockForUpdate()->findOrFail($line['item_id']);
            abort_unless($item->available, 409, 'Un article est indisponible.');
            $lines[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'unit_price' => $item->price,
                'quantity' => (int) $line['quantity'],
                'version' => $item->version,
                'minor_total' => Money::minor($item->price) * (int) $line['quantity'],
            ];
        }

        return $lines;
    }

    private function validateService(Restaurant $restaurant, array $input): void
    {
        if ($input['service_mode'] === 'delivery') {
            abort_unless($restaurant->delivery_enabled && $restaurant->delivery_fee !== null, 422, 'Livraison non configurée.');
        }
        $date = isset($input['arrival_at'])
            ? Carbon::parse($input['arrival_at'])->setTimezone($restaurant->timezone)
            : now($restaurant->timezone);
        abort_if(isset($input['arrival_at']) && $date->isPast(), 422, 'Heure d’arrivée dépassée.');
        $hours = DB::table('restaurant_hours')->where('restaurant_id', $restaurant->id)->get();
        // Aucune heure configurée signifie commandes fermées, pas ouverture permanente implicite.
        $open = $hours->contains(fn (object $slot) => (int) $slot->weekday === $date->dayOfWeekIso
            && $slot->opens_at <= $date->format('H:i') && $slot->closes_at > $date->format('H:i'));
        abort_unless($open, 422, 'Le restaurant est fermé à l’heure demandée.');
    }
}
