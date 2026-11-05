<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class CampaignController extends Controller
{
    public function __construct(private ApiAccess $access, private LoyaltyService $loyalty) {}

    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->active($restaurant);
        $rows = DB::table('campaigns')->where('restaurant_id', $restaurant->id)->where('active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>', now())->orderByDesc('id')->paginate(25);
        $rows->through(fn (object $row) => $this->publicCampaign($row));

        return response()->json($rows);
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['promotion', 'event', 'quiz'])],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'poster_url' => ['nullable', 'url:https', 'max:2048'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i:sP'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i:sP', 'after:starts_at'],
            'active' => ['required', 'boolean'],
            'rules' => ['required_if:kind,quiz', 'array:question,choices,answer_index,reward_points,max_winners'],
            'rules.question' => ['required_if:kind,quiz', 'string', 'max:500'],
            'rules.choices' => ['required_if:kind,quiz', 'array', 'min:2', 'max:6'],
            'rules.choices.*' => ['string', 'max:200', 'distinct'],
            'rules.answer_index' => ['required_if:kind,quiz', 'integer', 'between:0,5'],
            'rules.reward_points' => ['required_if:kind,quiz', 'integer', 'between:0,1000000'],
            'rules.max_winners' => ['required_if:kind,quiz', 'integer', 'between:1,10000'],
        ]);
        if ($data['kind'] === 'quiz') {
            abort_if($data['rules']['answer_index'] >= count($data['rules']['choices']), 422, 'Réponse hors des choix proposés.');
        }
        $id = DB::transaction(function () use ($request, $restaurant, $data): int {
            Restaurant::lockForUpdate()->findOrFail($restaurant->id);
            $rules = $data['kind'] === 'quiz' ? $data['rules'] : [];
            if (($rules['reward_points'] ?? 0) > 0) {
                $program = $this->loyalty->program($restaurant->id);
                // Un responsable local ne peut distribuer les points d'un programme global.
                abort_unless($program && (int) $program->restaurant_id === $restaurant->id, 422, 'Programme local approuvé requis pour récompenser le jeu.');
                $rules['program_id'] = $program->id;
            }
            $data['rules'] = json_encode($rules, JSON_THROW_ON_ERROR);
            $data['starts_at'] = Carbon::parse($data['starts_at'])->utc();
            $data['ends_at'] = Carbon::parse($data['ends_at'])->utc();
            $id = DB::table('campaigns')->insertGetId($data + ['restaurant_id' => $restaurant->id, 'created_at' => now(), 'updated_at' => now()]);
            Audit::record($request->user()->id, $restaurant->id, 'campaign.created', (string) $id);

            return $id;
        });

        return response()->json(['data' => $this->publicCampaign(DB::table('campaigns')->find($id))], 201);
    }

    public function deactivate(Request $request, Restaurant $restaurant, int $campaign): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        DB::transaction(function () use ($request, $restaurant, $campaign): void {
            $record = DB::table('campaigns')->where('restaurant_id', $restaurant->id)->where('id', $campaign)->lockForUpdate()->first();
            abort_unless($record, 404);
            DB::table('campaigns')->where('id', $campaign)->update(['active' => false, 'updated_at' => now()]);
            Audit::record($request->user()->id, $restaurant->id, 'campaign.deactivated', (string) $campaign);
        });

        return response()->json(['message' => 'Publication désactivée.']);
    }

    public function participate(Request $request, Restaurant $restaurant, int $campaign): JsonResponse
    {
        $this->access->active($restaurant);
        $data = $request->validate(['answer_index' => ['sometimes', 'integer', 'between:0,5']]);
        $entry = DB::transaction(function () use ($request, $restaurant, $campaign, $data): object {
            $record = DB::table('campaigns')->where('restaurant_id', $restaurant->id)->where('id', $campaign)->lockForUpdate()->first();
            abort_unless($record, 404);
            $existing = DB::table('campaign_entries')->where('campaign_id', $campaign)->where('user_id', $request->user()->id)->first();
            if ($existing) {
                $existing->won = (bool) $existing->won;

                return $existing;
            }
            abort_unless($record->active && now()->between($record->starts_at, $record->ends_at), 409, 'Événement indisponible.');
            abort_if($record->kind === 'promotion', 422, 'Une affiche promotionnelle ne comporte pas de participation.');
            $rules = json_decode($record->rules, true, flags: JSON_THROW_ON_ERROR);
            $won = $this->isWinner($record, $rules, $data);
            $id = DB::table('campaign_entries')->insertGetId([
                'campaign_id' => $campaign,
                'user_id' => $request->user()->id,
                'won' => $won,
                'created_at' => now(),
            ]);
            if ($won && ($rules['reward_points'] ?? 0) > 0) {
                $this->reward($rules, $request->user()->id, $id);
            }

            $entry = DB::table('campaign_entries')->find($id);
            $entry->won = (bool) $entry->won;

            return $entry;
        });

        return response()->json(['data' => $entry]);
    }

    private function isWinner(object $record, array $rules, array $input): bool
    {
        if ($record->kind !== 'quiz') {
            return false;
        }
        abort_unless(array_key_exists('answer_index', $input), 422, 'Sélectionnez une réponse.');
        $winners = DB::table('campaign_entries')->where('campaign_id', $record->id)->where('won', true)->count();

        return (int) $input['answer_index'] === (int) $rules['answer_index'] && $winners < $rules['max_winners'];
    }

    private function reward(array $rules, int $user, int $entry): void
    {
        $program = DB::table('loyalty_programs')->where('id', $rules['program_id'])->lockForUpdate()->first();
        abort_unless($program?->active, 409, 'Les récompenses de ce jeu sont suspendues.');
        $account = $this->loyalty->account($program->id, $user);
        $policy = json_decode($program->rules, true, flags: JSON_THROW_ON_ERROR);
        $this->loyalty->credit($account->id, null, 'quiz:'.$entry, (int) $rules['reward_points'], $policy);
    }

    private function publicCampaign(object $record): object
    {
        $rules = json_decode($record->rules, true, flags: JSON_THROW_ON_ERROR);
        unset($rules['answer_index']);
        $record->rules = $rules;

        return $record;
    }
}
