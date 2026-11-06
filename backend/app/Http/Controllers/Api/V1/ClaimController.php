<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimReply;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\ApiAccess;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ClaimController extends Controller
{
    public function __construct(private ApiAccess $access) {}

    public function store(Request $request, int $order): JsonResponse
    {
        $record = Order::where('customer_id', $request->user()->id)->findOrFail($order);
        $data = $request->validate(['subject' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'max:5000']]);
        $claim = new Claim;
        $claim->forceFill($data);
        $claim->restaurant_id = $record->restaurant_id;
        $claim->order_id = $record->id;
        $claim->author_id = $request->user()->id;
        $claim->save();

        return response()->json(['data' => $claim], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json(Claim::where('author_id', $request->user()->id)->latest('id')->paginate(25));
    }

    public function ownDetail(Request $request, int $claim): JsonResponse
    {
        $record = Claim::where('author_id', $request->user()->id)->findOrFail($claim);

        return response()->json([
            'data' => $record,
            // Les brouillons et réponses refusées ne sont jamais exposés au client.
            'replies' => ClaimReply::where('claim_id', $record->id)->where('state', 'approved')
                ->get(['id', 'body', 'reviewed_at']),
        ]);
    }

    public function restaurant(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);

        return response()->json(Claim::where('restaurant_id', $restaurant->id)->latest('id')->paginate(25));
    }

    public function detail(Request $request, Restaurant $restaurant, int $claim): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);
        $record = Claim::where('restaurant_id', $restaurant->id)->findOrFail($claim);

        return response()->json(['data' => $record, 'replies' => ClaimReply::where('claim_id', $record->id)->get()]);
    }

    public function reply(Request $request, Restaurant $restaurant, int $claim): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $reply = DB::transaction(function () use ($request, $restaurant, $claim, $data): ClaimReply {
            $record = Claim::where('restaurant_id', $restaurant->id)->lockForUpdate()->findOrFail($claim);
            abort_if($record->status === 'resolved', 409, 'Réclamation déjà résolue.');
            $reply = new ClaimReply;
            $reply->claim_id = $record->id;
            $reply->author_id = $request->user()->id;
            $reply->body = $data['body'];
            $reply->state = 'submitted';
            $reply->save();
            $record->status = 'awaiting_approval';
            $record->save();

            return $reply;
        });

        return response()->json(['data' => $reply], 201);
    }

    public function review(Request $request, Restaurant $restaurant, int $claim, int $reply): JsonResponse
    {
        $this->access->staff($request->user(), $restaurant, true);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])]]);
        $record = DB::transaction(function () use ($request, $restaurant, $claim, $reply, $data): ClaimReply {
            $claimRecord = Claim::where('restaurant_id', $restaurant->id)->lockForUpdate()->findOrFail($claim);
            $record = ClaimReply::where('claim_id', $claimRecord->id)->lockForUpdate()->findOrFail($reply);
            abort_if($record->author_id === $request->user()->id, 403, 'Une autre personne doit valider la réponse.');
            abort_if($record->state !== 'submitted' || $claimRecord->status === 'resolved', 409, 'Réponse déjà traitée.');
            $record->state = $data['decision'];
            $record->reviewer_id = $request->user()->id;
            $record->reviewed_at = now();
            $record->save();
            $claimRecord->status = $record->state === 'approved' ? 'resolved' : 'open';
            $claimRecord->save();
            Audit::record($request->user()->id, $restaurant->id, 'claim.'.$record->state, (string) $claimRecord->id);

            return $record;
        });

        return response()->json(['data' => $record]);
    }
}
