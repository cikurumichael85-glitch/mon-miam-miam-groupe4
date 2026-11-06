<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function csrf(): JsonResponse
    {
        // Le middleware web ajoute le cookie XSRF-TOKEN à cette réponse.
        return response()->json(['csrf_token' => csrf_token()]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'phone' => ['required', 'string', 'max:32'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->phone = $data['phone'];
        $user->delivery_address = $data['delivery_address'] ?? null;
        $user->status = AccountStatus::Active;
        // L'inscription publique ne lit aucun rôle fourni par le navigateur.
        $user->save();
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $user], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string', 'max:1000']]);
        $credentials = [
            'email' => Str::lower(trim($data['email'])),
            'password' => $data['password'],
            'status' => AccountStatus::Active->value,
        ];
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Identifiants invalides ou compte désactivé.']);
        }
        $request->session()->regenerate();

        return $this->me($request);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $user->load('memberships')->toArray();
        $data['platform_access'] = DB::table('platform_grants')->where('user_id', $user->id)->where('active', true)->exists();

        return response()->json(['data' => $data]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }

    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => Str::lower(trim($data['email']))]);

        // Même réponse pour les adresses inconnues afin de limiter leur énumération.
        return response()->json(['message' => 'Si ce compte existe, un lien de récupération sera envoyé.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['token' => 'Lien invalide ou expiré.']);
        }

        return response()->json(['message' => 'Mot de passe modifié.']);
    }

    private function passwordRules(): array
    {
        return AccountPassword::rules();
    }
}
