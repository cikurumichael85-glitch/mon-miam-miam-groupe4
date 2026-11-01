<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class MenuController extends Controller
{
    public function __construct(private ApiAccess $access) {}

    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->active($restaurant);
        $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']]);

        return response()->json([
            'categories' => MenuCategory::where('restaurant_id', $restaurant->id)->orderBy('position')->get(),
            'items' => MenuItem::where('restaurant_id', $restaurant->id)->whereNull('archived_at')->orderBy('id')->paginate(50),
        ]);
    }

    public function category(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'position' => ['sometimes', 'integer', 'between:0,10000']]);
        $category = new MenuCategory;
        $category->restaurant_id = $restaurant->id;
        $category->name = $data['name'];
        $category->position = $data['position'] ?? 0;
        $category->save();

        return response()->json(['data' => $category], 201);
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate($this->rules());
        MenuCategory::where('restaurant_id', $restaurant->id)->findOrFail($data['category_id']);
        $item = DB::transaction(function () use ($restaurant, $data, $request): MenuItem {
            $item = new MenuItem;
            $item->forceFill($data);
            $item->restaurant_id = $restaurant->id;
            $item->save();
            Audit::record($request->user()->id, $restaurant->id, 'menu.created', (string) $item->id);

            return $item;
        });

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, Restaurant $restaurant, int $item): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate($this->rules() + ['expected_version' => ['required', 'integer', 'min:1']]);
        MenuCategory::where('restaurant_id', $restaurant->id)->findOrFail($data['category_id']);

        return $this->mutate($request, $restaurant, $item, $data);
    }

    public function availability(Request $request, Restaurant $restaurant, int $item): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);
        $data = $request->validate([
            'available' => ['required', 'boolean'],
            'is_daily' => ['sometimes', 'boolean'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        return $this->mutate($request, $restaurant, $item, $data);
    }

    public function archive(Request $request, Restaurant $restaurant, int $item): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);

        return $this->mutate($request, $restaurant, $item, $data + ['archived_at' => now(), 'available' => false]);
    }

    private function mutate(Request $request, Restaurant $restaurant, int $id, array $data): JsonResponse
    {
        $item = DB::transaction(function () use ($request, $restaurant, $id, $data): MenuItem {
            $item = MenuItem::where('restaurant_id', $restaurant->id)->lockForUpdate()->findOrFail($id);
            abort_if($item->version !== (int) $data['expected_version'], 409, 'Le menu a changé.');
            unset($data['expected_version']);
            $item->forceFill($data);
            $item->version++;
            $item->save();
            Audit::record($request->user()->id, $restaurant->id, 'menu.updated', (string) $item->id);

            return $item;
        });

        return response()->json(['data' => $item]);
    }

    private function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'string', 'regex:'.Money::INPUT_PATTERN],
            'available' => ['sometimes', 'boolean'],
            'is_daily' => ['sometimes', 'boolean'],
        ];
    }
}
