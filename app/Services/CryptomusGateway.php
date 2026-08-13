<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;

class CryptomusGateway
{
    private const ENDPOINT = 'https://api.cryptomus.com/v1/payment';

    public function __construct(private readonly PaymentMethod $paymentMethod) {}

    /**
     * Create a Cryptomus invoice for an Order and return the invoice uuid plus
     * the hosted payment URL. Reusing the same order_id returns the existing
     * invoice, so retries never duplicate.
     *
     * @return array{uuid: string, url: string, payment_status: string}
     */
    public function createInvoice(Order $order): array
    {
        $body = [
            'amount' => number_format($order->settlementAmount(), 2, '.', ''),
            'currency' => strtoupper($order->settlement_currency->value),
            'order_id' => $order->number,
            'url_return' => route('orders.show', $order),
            'url_success' => route('orders.show', $order),
            'url_callback' => route('cryptomus.notification'),
        ];

        $json = $this->encode($body);

        $response = Http::withHeaders([
            'merchant' => $this->paymentMethod->cryptomusMerchantUuid(),
            'sign' => $this->sign($json),
        ])->withBody($json, 'application/json')->post(self::ENDPOINT);

        $response->throw();

        $result = $response->json('result') ?? [];

        return [
            'uuid' => (string) ($result['uuid'] ?? ''),
            'url' => (string) ($result['url'] ?? ''),
            'payment_status' => (string) ($result['payment_status'] ?? ''),
        ];
    }

    /**
     * Verify the Cryptomus webhook signature: md5 of the base64-encoded JSON
     * body (without the sign field) concatenated with the payment API key.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifySignature(array $payload, ?string $apiKey = null): bool
    {
        $received = (string) ($payload['sign'] ?? '');
        $apiKey ??= $this->paymentMethod->cryptomusPaymentApiKey();

        unset($payload['sign']);

        return hash_equals($received, $this->sign($this->encode($payload)));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function encode(array $body): string
    {
        return (string) json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    private function sign(string $json): string
    {
        return md5(base64_encode($json).$this->paymentMethod->cryptomusPaymentApiKey());
    }
}
