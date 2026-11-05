<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Services\ErasureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class PrivacyController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:32'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'ranking_opt_in' => ['required', 'boolean'],
            'public_alias' => ['required_if:ranking_opt_in,true,1', 'nullable', 'string', 'max:50'],
            'analytics_consent' => ['required', 'boolean'],
        ]);
        $user = $request->user();
        $user->forceFill($data);
        $user->save();

        return response()->json(['data' => $user]);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        $orders = Order::where('customer_id', $user->id)->get();
        $accounts = DB::table('loyalty_accounts')->where('user_id', $user->id)->pluck('id');

        return response()->json([
            'profile' => $user,
            'orders' => $orders,
            'lines' => DB::table('order_lines')->whereIn('order_id', $orders->pluck('id'))->get(),
            'claims' => DB::table('claims')->where('author_id', $user->id)->get(),
            'reviews' => DB::table('reviews')->where('user_id', $user->id)->get(),
            'loyalty' => DB::table('loyalty_entries')->whereIn('account_id', $accounts)->get(),
            'generated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store')->header('Content-Disposition', 'attachment; filename="mes-donnees.json"');
    }

    public function requestErasure(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        DB::table('privacy_requests')->insert([
            'user_id' => $request->user()->id,
            'kind' => 'erasure',
            'created_at' => now(),
        ]);

        // Les durées de conservation ne sont pas inventées ; un responsable instruit la demande.
        return response()->json(['message' => 'Demande reçue. Le responsable vérifiera les données à effacer et celles à conserver.'], 202);
    }

    public function requests(Request $request): JsonResponse
    {
        app(ApiAccess::class)->platform($request->user());

        return response()->json(DB::table('privacy_requests')->orderByDesc('id')->paginate(25));
    }

    public function process(Request $request, int $privacyRequest): JsonResponse
    {
        app(ApiAccess::class)->platform($request->user());
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'rejected'])],
            'resolution' => ['required', 'string', 'max:3000'],
        ]);
        abort_unless(DB::table('privacy_requests')->where('id', $privacyRequest)->exists(), 404);
        DB::table('privacy_requests')->where('id', $privacyRequest)->update($data);
        Audit::record($request->user()->id, null, 'privacy.reviewed', (string) $privacyRequest);

        return response()->json(['message' => 'Instruction de la demande enregistrée.']);
    }

    public function anonymize(Request $request, int $privacyRequest, ErasureService $erasure): JsonResponse
    {
        app(ApiAccess::class)->platform($request->user());
        $request->validate(['password' => ['required', 'current_password'], 'confirm' => ['required', 'accepted']]);
        $erasure->execute($privacyRequest, $request->user()->id);

        return response()->json(['message' => 'Anonymisation applicative effectuée. Les sauvegardes et le prestataire suivent leur procédure séparée.']);
    }
}
