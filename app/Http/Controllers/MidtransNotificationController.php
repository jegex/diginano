<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderStatus;
use App\Services\MidtransGateway;
use App\Services\OrderFinalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->input();

        $order = Order::query()
            ->where('number', (string) ($payload['order_id'] ?? ''))
            ->first();

        if ($order === null || ! $order->isMidtransPayment()) {
            return response()->noContent(404);
        }

        if (! (new MidtransGateway($order->paymentMethod))->verifySignature($payload)) {
            return response()->noContent(403);
        }

        $this->handlePaymentStatus($order, $payload);

        return response()->noContent(200);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handlePaymentStatus(Order $order, array $payload): void
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        $this->recordProviderInfo($order, $payload);

        if ($order->status->isTerminal()) {
            return;
        }

        $shouldFinalize = match ($transactionStatus) {
            'settlement' => true,
            'capture' => $fraudStatus === 'accept',
            default => false,
        };

        if ($shouldFinalize) {
            app(OrderFinalizer::class)->finalize($order);

            return;
        }

        match ($transactionStatus) {
            'deny', 'cancel', 'expire', 'failure' => $order->update(['status' => OrderStatus::Cancelled]),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordProviderInfo(Order $order, array $payload): void
    {
        $order->update([
            'provider_reference' => (string) ($payload['transaction_id'] ?? '') ?: null,
            'provider_status' => (string) ($payload['transaction_status'] ?? '') ?: null,
            'payment_type' => (string) ($payload['payment_type'] ?? '') ?: null,
        ]);
    }
}
