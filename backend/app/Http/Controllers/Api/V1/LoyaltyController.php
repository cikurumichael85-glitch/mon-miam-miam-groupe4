<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Services\LoyaltyService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LoyaltyController extends Controller
{
    public function __construct(private ApiAccess $access, private LoyaltyService $loyalty) {}

    public function program(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);

        return $this->saveProgram($request, $restaurant->id);
    }

    public function showProgram(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        // Lire uniquement la politique locale ; ne jamais éditer le programme global par défaut.
        $program = DB::table('loyalty_programs')->where('restaurant_id', $restaurant->id)->first();
        if (! $program) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'active' => (bool) $program->active,
            'version' => (int) $program->version,
            'rules' => json_decode($program->rules, true, flags: JSON_THROW_ON_ERROR),
            'approved_at' => $program->approved_at,
        ]]);
    }

    public function platformProgram(Request $request): JsonResponse
    {
        $this->access->platform($request->user());

        return $this->saveProgram($request, null);
    }

    private function saveProgram(Request $request, ?int $restaurant): JsonResponse
    {
        $data = $request->validate([
            'active' => ['required', 'boolean'],
            'approval' => ['required', 'accepted'],
            'rules' => ['required', 'array:earn_amount,redeem_points,redeem_value,referral_points,expiry_days,require_paid'],
            'rules.earn_amount' => ['required', 'string', 'regex:'.Money::INPUT_PATTERN],
            'rules.redeem_points' => ['required', 'integer', 'between:1,1000000'],
            'rules.redeem_value' => ['required', 'string', 'regex:'.Money::INPUT_PATTERN],
            'rules.referral_points' => ['required', 'integer', 'between:0,1000000'],
            'rules.expiry_days' => ['required', 'integer', 'between:1,3650'],
            'rules.require_paid' => ['required', 'boolean'],
        ]);
        abort_if(Money::minor($data['rules']['earn_amount']) < 100 || Money::minor($data['rules']['redeem_value']) === 0, 422, 'Barème incompatible : au moins 1 FC par point et une remise positive.');
        $program = DB::transaction(function () use ($request, $restaurant, $data): object {
            $scope = $restaurant === null ? 'platform' : 'restaurant:'.$restaurant;
            $existing = DB::table('loyalty_programs')->where('scope_key', $scope)->lockForUpdate()->first();
            $rules = $this->normalizeRules($data['rules']);
            if ($existing) {
                // Une politique utilisée reste immuable : les commandes conservent leur contrat.
                $used = Order::where('loyalty_program_id', $existing->id)->exists()
                    || DB::table('loyalty_accounts')->where('program_id', $existing->id)->exists();
                $changed = json_decode($existing->rules, true, flags: JSON_THROW_ON_ERROR) != $rules;
                abort_if($used && $changed, 409, 'Programme déjà utilisé : une migration de politique est nécessaire.');
            }
            DB::table('loyalty_programs')->updateOrInsert(['scope_key' => $scope], [
                'restaurant_id' => $restaurant,
                'active' => $data['active'],
                'rules' => json_encode($rules, JSON_THROW_ON_ERROR),
                'version' => ($existing?->version ?? 0) + 1,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
                'updated_at' => now(),
            ]);
            Audit::record($request->user()->id, $restaurant, 'loyalty.approved', $scope);

            return DB::table('loyalty_programs')->where('scope_key', $scope)->first();
        });

        return response()->json(['data' => $program]);
    }

    private function normalizeRules(array $rules): array
    {
        return [
            'earn_amount' => Money::decimal(Money::minor($rules['earn_amount'])),
            'redeem_points' => (int) $rules['redeem_points'],
            'redeem_value' => Money::decimal(Money::minor($rules['redeem_value'])),
            'referral_points' => (int) $rules['referral_points'],
            'expiry_days' => (int) $rules['expiry_days'],
            'require_paid' => (bool) $rules['require_paid'],
        ];
    }

    public function accounts(Request $request): JsonResponse
    {
        $accounts = DB::table('loyalty_accounts')->where('user_id', $request->user()->id)->orderBy('program_id')->get();
        $rows = $accounts->map(function (object $account): object {
            return DB::transaction(function () use ($account): object {
                $program = DB::table('loyalty_programs')->where('id', $account->program_id)->lockForUpdate()->first();
                $account = $this->loyalty->account($account->program_id, $account->user_id);
                $this->loyalty->expire($account->id);
                $account->balance = (int) DB::table('loyalty_entries')->where('account_id', $account->id)->sum('delta');
                $account->program = $program;

                return $account;
            });
        });

        return response()->json(['data' => $rows]);
    }

    public function entries(Request $request, int $account): JsonResponse
    {
        abort_unless(DB::table('loyalty_accounts')->where('id', $account)->where('user_id', $request->user()->id)->exists(), 404);

        return response()->json(DB::table('loyalty_entries')->where('account_id', $account)->orderByDesc('id')->paginate(50));
    }

    public function referralCode(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->active($restaurant);
        $code = DB::transaction(function () use ($request, $restaurant): object {
            $program = $this->loyalty->program($restaurant->id);
            abort_unless($program, 422, 'Aucun programme actif.');
            DB::table('referral_codes')->insertOrIgnore([
                'program_id' => $program->id,
                'owner_id' => $request->user()->id,
                'code' => (string) Str::uuid(),
            ]);

            return DB::table('referral_codes')->where('program_id', $program->id)->where('owner_id', $request->user()->id)->first();
        });

        return response()->json(['data' => $code]);
    }

    public function refer(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'uuid']]);
        DB::transaction(function () use ($request, $data): void {
            $code = DB::table('referral_codes')->where('code', $data['code'])->first();
            abort_unless($code, 422, 'Code non admissible.');
            $program = DB::table('loyalty_programs')->where('id', $code->program_id)->where('active', true)->lockForUpdate()->first();
            abort_unless($program && $code->owner_id !== $request->user()->id, 422, 'Code non admissible.');
            $prior = Order::where('customer_id', $request->user()->id)->where('loyalty_program_id', $program->id)->exists();
            abort_if($prior, 409, 'Le code doit être rattaché avant la première commande de ce programme.');
            $existing = DB::table('referrals')->where('program_id', $program->id)->where('referred_user_id', $request->user()->id)->first();
            if ($existing) {
                abort_if($existing->code_id !== $code->id, 409, 'Un parrain est déjà associé.');

                return;
            }
            DB::table('referrals')->insert([
                'program_id' => $program->id,
                'code_id' => $code->id,
                'referred_user_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Code de parrainage enregistré.']);
    }

    public function referrals(Request $request): JsonResponse
    {
        $codes = DB::table('referral_codes')->where('owner_id', $request->user()->id)->pluck('id');
        $rows = DB::table('referrals')->whereIn('code_id', $codes)->get(['id', 'program_id', 'created_at', 'rewarded_at']);

        return response()->json(['data' => $rows]);
    }
}
