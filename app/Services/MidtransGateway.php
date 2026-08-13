<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;

class MidtransGateway
{
    private const SANDBOX_ENDPOINT = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    private const PRODUCTION_ENDPOINT = 'https://app.midtrans.com/snap/v1/transactions';

    public function __construct(private readonly PaymentMethod $paymentMethod) {}

    /**
     * Create a Snap token for an Order and return the token plus the hosted
     * redirect URL. The Order must already exist so Midtrans order_id stays
     * unique per transaction attempt.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function createTransaction(Order $order): array
    {
        $response = Http::withBasicAuth($this->paymentMethod->midtransServerKey(), '')
            ->acceptJson()
            ->post($this->endpoint(), [
                'transaction_details' => [
                    'order_id' => $order->number,
                    'gross_amount' => (int) round($order->settlementAmount()),
                ],
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'callbacks' => [
                    'finish' => route('orders.show', $order),
                ],
            ]);

        $response->throw();

        $payload = $response->json();

        return [
            'token' => (string) ($payload['token'] ?? ''),
            'redirect_url' => (string) ($payload['redirect_url'] ?? ''),
        ];
    }

    /**
     * Verify the SHA512 notification signature over
     * order_id + status_code + gross_amount + serverKey, using the raw
     * gross_amount string exactly as received.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, ?string $serverKey = null): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $serverKey ??= $this->paymentMethod->midtransServerKey();

        return hash_equals($signatureKey, hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey));
    }

    private function endpoint(): string
    {
        return $this->paymentMethod->midtransIsSandbox()
            ? self::SANDBOX_ENDPOINT
            : self::PRODUCTION_ENDPOINT;
    }
}
