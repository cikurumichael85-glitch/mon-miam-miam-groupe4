<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRestaurantsRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class ListRestaurantsController extends Controller
{
    public function __invoke(ListRestaurantsRequest $request): JsonResponse
    {
        $parameters = $request->validated();
        // Le filtre actif est imposé par le serveur, même si le client demande les inactifs.
        $restaurants = Restaurant::query()
            ->where('status', RestaurantStatus::Active->value)
            ->orderBy('id')
            ->paginate(
                perPage: (int) ($parameters['per_page'] ?? 20),
                columns: ['id', 'name', 'timezone', 'status', 'delivery_enabled'],
                page: (int) ($parameters['page'] ?? 1),
            );

        // Le contrat expose data et meta, sans ajouter les champs privés du modèle.
        return response()->json([
            'data' => RestaurantResource::collection($restaurants->getCollection())->resolve($request),
            'meta' => [
                'page' => $restaurants->currentPage(),
                'per_page' => $restaurants->perPage(),
                'total' => $restaurants->total(),
            ],
        ]);
    }
}
