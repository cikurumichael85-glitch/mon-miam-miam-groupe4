<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Support\RestaurantInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class RestaurantAdminController extends Controller
{
    public function __construct(private ApiAccess $access) {}

    public function store(Request $request): JsonResponse
    {
        $this->access->platform($request->user());
        $data = $request->validate(RestaurantInput::rules());
        $restaurant = new Restaurant;
        $restaurant->forceFill($data);
        $restaurant->status = RestaurantStatus::Inactive;
        $restaurant->save();

        return response()->json(['data' => $restaurant], 201);
    }

    public function platformUpdate(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->platform($request->user());
        $data = $request->validate(RestaurantInput::rules() + [
            'status' => ['required', Rule::enum(RestaurantStatus::class)],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        return $this->save($request, $restaurant, $data);
    }

    public function settings(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->administrator($request->user(), $restaurant);
        $data = $request->validate(RestaurantInput::rules() + ['expected_version' => ['required', 'integer', 'min:1']]);

        return $this->save($request, $restaurant, $data);
    }

    public function hours(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->administrator($request->user(), $restaurant);
        $data = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'hours' => ['present', 'array', 'max:28'],
            'hours.*' => ['array:weekday,opens_at,closes_at'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7'],
            'hours.*.opens_at' => ['required', 'date_format:H:i'],
            'hours.*.closes_at' => ['required', 'date_format:H:i'],
        ]);
        $this->validateSlots($data['hours']);
        DB::transaction(function () use ($data, $restaurant, $request): void {
            $record = Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            abort_if($record->settings_version !== (int) $data['expected_version'], 409, 'Paramètres modifiés.');
            DB::table('restaurant_hours')->where('restaurant_id', $record->id)->delete();
            foreach ($data['hours'] as $slot) {
                DB::table('restaurant_hours')->insert($slot + ['restaurant_id' => $record->id]);
            }
            $record->settings_version++;
            $record->save();
            Audit::record($request->user()->id, $record->id, 'hours.updated', (string) $record->id);
        });

        return response()->json(['data' => $restaurant->fresh()]);
    }

    public function showSettings(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->administrator($request->user(), $restaurant);
        // Lire une version cohérente du restaurant et de ses horaires.
        $snapshot = DB::transaction(function () use ($restaurant): array {
            $record = Restaurant::lockForUpdate()->findOrFail($restaurant->id);

            return [
                'data' => $record->only(['id', 'name', 'address', 'timezone', 'delivery_enabled', 'delivery_fee', 'settings_version']),
                'hours' => DB::table('restaurant_hours')->where('restaurant_id', $record->id)
                    ->orderBy('weekday')->orderBy('opens_at')->get(['weekday', 'opens_at', 'closes_at']),
            ];
        });

        return response()->json($snapshot);
    }

    private function validateSlots(array $slots): void
    {
        usort($slots, fn (array $a, array $b) => [$a['weekday'], $a['opens_at']] <=> [$b['weekday'], $b['opens_at']]);
        $previous = [];
        foreach ($slots as $slot) {
            abort_if($slot['opens_at'] >= $slot['closes_at'], 422, 'Fractionnez les horaires qui passent minuit.');
            abort_if(($previous[$slot['weekday']] ?? '') > $slot['opens_at'], 422, 'Horaires qui se chevauchent.');
            $previous[$slot['weekday']] = $slot['closes_at'];
        }
    }

    private function save(Request $request, Restaurant $restaurant, array $data): JsonResponse
    {
        $record = DB::transaction(function () use ($request, $restaurant, $data): Restaurant {
            $record = Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            abort_if($record->settings_version !== (int) $data['expected_version'], 409, 'Paramètres modifiés.');
            unset($data['expected_version']);
            $record->forceFill($data);
            $record->settings_version++;
            $record->save();
            Audit::record($request->user()->id, $record->id, 'restaurant.updated', (string) $record->id);

            return $record;
        });

        return response()->json(['data' => $record]);
    }
}
