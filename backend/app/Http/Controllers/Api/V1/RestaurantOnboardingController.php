<?php

// Prise en main : Ryan ; un nouveau restaurant n'accorde aucun droit sur les autres.

namespace App\Http\Controllers\Api\V1;

use App\Enums\RestaurantRole;
use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Services\Audit;
use App\Support\RestaurantInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RestaurantOnboardingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request): array {
            // Relire le droit actif en base : aucun rôle transmis par le client n'est accepté.
            $administrator = RestaurantMembership::where('user_id', $request->user()->id)
                ->where('active', true)->where('role', RestaurantRole::Administrator->value)
                ->whereHas('restaurant', fn ($query) => $query->where('status', RestaurantStatus::Active->value))
                ->lockForUpdate()->first();
            abort_unless($administrator, 403, 'Seul un administrateur de restaurant peut ajouter un établissement.');
            $data = $request->validate(RestaurantInput::rules());
            $restaurant = new Restaurant;
            $restaurant->forceFill($data);
            $restaurant->status = RestaurantStatus::Active;
            $restaurant->save();
            $membership = new RestaurantMembership;
            $membership->restaurant_id = $restaurant->id;
            $membership->user_id = $request->user()->id;
            $membership->role = RestaurantRole::Administrator;
            $membership->active = true;
            $membership->save();
            Audit::record($request->user()->id, $restaurant->id, 'restaurant.owner_created', (string) $restaurant->id);

            return ['restaurant' => $restaurant->fresh(), 'membership' => $membership];
        }, 3);

        // Carte, horaires et paiement restent propres à chaque établissement, jamais recopiés.
        return response()->json(['data' => $created['restaurant'], 'membership' => $created['membership']], 201);
    }
}
