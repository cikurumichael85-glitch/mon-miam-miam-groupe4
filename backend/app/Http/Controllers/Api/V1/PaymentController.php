<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ApiAccess;
use App\Services\Audit;
use App\Services\EasyPayGateway;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments, private EasyPayGateway $gateway) {}

    public function start(Request $request, int $order): JsonResponse
    {
        $record = Order::where('customer_id', $request->user()->id)->findOrFail($order);
        $data = $request->validate(['customer_phone' => ['sometimes', 'required', 'string', 'max:30']]);
        $phone = isset($data['customer_phone']) ? $this->nationalPhone($data['customer_phone']) : null;
        $payment = $this->payments->start($record, $request->user()->name, $phone);

        return response()->json([
            'data' => $payment,
            'notification_requested' => $phone !== null,
            'checkout_url' => $phone === null ? $this->gateway->checkoutUrl($payment) : null,
        ]);
    }

    private function nationalPhone(string $value): string
    {
        $compact = preg_replace('/[\s()-]+/', '', $value);
        abort_unless(preg_match('/^(?:\+?243|0)?([1-9][0-9]{8})$/', $compact, $matches), 422, 'Saisissez un numéro RDC valide, par exemple 0980123456 ou +243980123456.');

        return $matches[1];
    }

    public function verify(Request $request, int $order): JsonResponse
    {
        Order::where('customer_id', $request->user()->id)->findOrFail($order);
        $payment = Payment::where('order_id', $order)->where('is_active', true)->firstOrFail();

        return response()->json(['data' => $this->payments->verify($payment)]);
    }

    public function notify(Request $request): JsonResponse
    {
        $data = $request->validate(['transaction.reference' => ['required', 'string', 'max:150']]);
        $payment = Payment::where('external_reference', $data['transaction']['reference'])->where('is_active', true)->first();
        if ($payment) {
            // L'IPN n'est qu'un signal : le statut reçu n'est jamais utilisé comme preuve.
            $this->payments->verify($payment);
        }

        return response()->json(['received' => true]);
    }

    public function reconcile(Request $request, int $payment): JsonResponse
    {
        app(ApiAccess::class)->platform($request->user());
        $data = $request->validate(['reference' => ['required', 'string', 'regex:/^[A-Za-z0-9=_-]{1,150}$/']]);
        $record = $this->payments->reconcile(Payment::findOrFail($payment), $data['reference']);
        Audit::record($request->user()->id, $record->restaurant_id, 'payment.reconciled', (string) $record->id);

        return response()->json(['data' => $record]);
    }
}
