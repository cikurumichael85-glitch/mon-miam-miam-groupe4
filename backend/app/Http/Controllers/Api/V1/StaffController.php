<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Enums\RestaurantRole;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantMembership;
use App\Models\User;
use App\Services\ApiAccess;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StaffController extends Controller
{
    public function __construct(private ApiAccess $access) {}

    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);

        return response()->json(['data' => RestaurantMembership::where('restaurant_id', $restaurant->id)
            ->with('user:id,name,email,status')->get()]);
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));
        $user = DB::transaction(function () use ($request, $restaurant, $data, $email): User {
            Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $user = User::firstOrCreate(['email' => $email], ['name' => $data['name'], 'password' => Str::random(64)]);
            $user->refresh();
            abort_unless($user->status === AccountStatus::Active, 422, 'Compte indisponible.');
            $membership = RestaurantMembership::where('restaurant_id', $restaurant->id)->where('user_id', $user->id)->first();
            abort_if($membership !== null, 409, 'Ce compte possède déjà un rattachement.');
            $membership = new RestaurantMembership;
            $membership->restaurant_id = $restaurant->id;
            $membership->user_id = $user->id;
            $membership->role = RestaurantRole::Employee;
            $membership->active = true;
            $membership->save();
            Audit::record($request->user()->id, $restaurant->id, 'employee.created', (string) $user->id);

            return $user;
        });
        // Un compte existant garde son mot de passe ; seul un nouveau compte reçoit le lien.
        if ($user->wasRecentlyCreated) {
            Password::sendResetLink(['email' => $email]);
        }

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name]], 201);
    }

    public function update(Request $request, Restaurant $restaurant, int $user): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $membership = RestaurantMembership::where('restaurant_id', $restaurant->id)->where('user_id', $user)->firstOrFail();
        abort_unless($membership->role === RestaurantRole::Employee, 403, 'La direction relève de la plateforme.');
        $membership->active = $data['active'];
        $membership->save();
        Audit::record($request->user()->id, $restaurant->id, 'employee.access_updated', (string) $user);

        return response()->json(['data' => $membership]);
    }

    public function manager(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->platform($request->user());
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(['manager', 'restaurant_admin'])],
            'active' => ['required', 'boolean'],
        ]);
        DB::transaction(function () use ($request, $restaurant, $data): void {
            Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            DB::table('restaurant_memberships')->updateOrInsert(
                ['restaurant_id' => $restaurant->id, 'user_id' => $data['user_id']],
                ['role' => $data['role'], 'active' => $data['active'], 'updated_at' => now(), 'created_at' => now()],
            );
            Audit::record($request->user()->id, $restaurant->id, 'manager.access_updated', (string) $data['user_id']);
        });

        return response()->json(['message' => 'Responsabilité mise à jour.']);
    }

    public function assignManager(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->administrator($request->user(), $restaurant);
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'active' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $restaurant, $data): void {
            Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $this->access->administrator($request->user(), $restaurant);
            $account = User::where('email', Str::lower(trim($data['email'])))->lockForUpdate()->first();
            abort_unless($account && $account->status === AccountStatus::Active, 422, 'Le titulaire doit d’abord inscrire un compte actif avec cette adresse.');
            $membership = RestaurantMembership::where('restaurant_id', $restaurant->id)->where('user_id', $account->id)->lockForUpdate()->first();
            abort_if($membership?->role === RestaurantRole::Administrator, 403, 'Un administrateur ne peut pas être modifié par cette opération.');
            if (! $data['active']) {
                abort_unless($membership?->role === RestaurantRole::Manager, 422, 'Aucun accès de gérant à révoquer pour ce compte.');
            }
            $membership ??= new RestaurantMembership;
            $membership->restaurant_id = $restaurant->id;
            $membership->user_id = $account->id;
            $membership->role = RestaurantRole::Manager;
            $membership->active = $data['active'];
            $membership->save();
            Audit::record($request->user()->id, $restaurant->id, 'manager.local_access_updated', (string) $account->id);
        });

        return response()->json(['message' => 'Accès de gérant mis à jour pour ce restaurant. Le mot de passe reste inchangé.']);
    }
}
