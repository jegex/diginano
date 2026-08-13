<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderStatus;
use App\Services\CryptomusGateway;
use App\Services\OrderFinalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CryptomusNotificationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->input();

        $order = Order::query()
            ->where('number', (string) ($payload['order_id'] ?? ''))
            ->first();

        if ($order === null || ! $order->isCryptomusPayment()) {
            return response()->noContent(404);
        }

        if (! (new CryptomusGateway($order->paymentMethod))->verifySignature($payload)) {
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
        $this->recordProviderInfo($order, $payload);

        if ($order->status->isTerminal()) {
            return;
        }

        $status = (string) ($payload['status'] ?? '');

        if (in_array($status, ['paid', 'paid_over'], true)) {
            app(OrderFinalizer::class)->finalize($order);

            return;
        }

        if (in_array($status, ['fail', 'cancel', 'system_fail'], true)) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordProviderInfo(Order $order, array $payload): void
    {
        $order->update([
            'provider_reference' => (string) ($payload['uuid'] ?? '') ?: null,
            'provider_status' => (string) ($payload['status'] ?? '') ?: null,
            'payment_type' => (string) ($payload['payer_currency'] ?? '') ?: null,
        ]);
    }
}
