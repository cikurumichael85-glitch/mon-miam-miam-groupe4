<?php

// Prise en main : Yedidia ; aucun administrateur ne peut lire le mot de passe d'un tiers.

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Support\AccountPassword;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AccountCredentialsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        }
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:1000'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'password' => array_merge(['sometimes'], AccountPassword::rules()),
        ]);
        if (! isset($data['email']) && ! isset($data['password'])) {
            throw ValidationException::withMessages(['email' => 'Indiquez une adresse ou un nouveau mot de passe.']);
        }
        try {
            $this->save($request, $data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'Cette adresse est déjà utilisée.']);
        }
        // Le compte doit se reconnecter ; aucun secret n'est renvoyé ou journalisé.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Identifiants mis à jour. Reconnectez-vous avec vos nouveaux accès.']);
    }

    private function save(Request $request, array $data): void
    {
        DB::transaction(function () use ($request, $data): void {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->status === AccountStatus::Active, 403, 'Compte désactivé.');
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Mot de passe actuel incorrect.']);
            }
            if (isset($data['email']) && $data['email'] !== $user->email) {
                $user->email = $data['email'];
                $user->email_verified_at = null;
            }
            if (isset($data['password'])) {
                $user->password = $data['password'];
            }
            $user->setRememberToken(Str::random(60));
            $user->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->delete();
            }
            Audit::record($user->id, null, 'account.credentials_updated', (string) $user->id);
        }, 3);
    }
}
