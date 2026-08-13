<?php

use App\Enums\OrderStatus;
use App\Livewire\CheckoutPage;
use App\Livewire\OrderReceipt;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\postJson;

const CRYPTOMUS_MERCHANT_UUID = 'merchant-uuid';

const CRYPTOMUS_PAYMENT_API_KEY = 'api-key';

function makeCryptomusOrder(User $user, array $plans = []): Order
{
    auth()->login($user);
    seedCurrencies();
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->cryptomus()->create();

    foreach ($plans as [$plan, $qty]) {
        $cart->add($plan, $qty);
    }

    return Order::checkout($cart, $method);
}

/**
 * @param  array<string, mixed>  $payload
 */
function cryptomusSignature(array $payload): string
{
    unset($payload['sign']);

    return md5(base64_encode((string) json_encode($payload, JSON_UNESCAPED_UNICODE)).CRYPTOMUS_PAYMENT_API_KEY);
}

it('redirects to the Cryptomus payment page after checkout and stores the invoice reference', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $method = PaymentMethod::factory()->cryptomus()->create();
    Cart::for($user)->add($plan, 1);

    Http::fake([
        'api.cryptomus.com/*' => Http::response([
            'state' => 0,
            'result' => [
                'uuid' => 'invoice-uuid-123',
                'url' => 'https://pay.cryptomus.com/pay/invoice-uuid-123',
                'payment_status' => 'check',
            ],
        ], 200),
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->isCryptomusPayment())->toBeTrue()
        ->and($order->settlement_currency)->toBe('usd')
        ->and($order->provider_reference)->toBe('invoice-uuid-123')
        ->and($order->provider_status)->toBe('check');

    Http::assertSent(function (Request $request) use ($order): bool {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://api.cryptomus.com/v1/payment'
            && $request->hasHeader('merchant', CRYPTOMUS_MERCHANT_UUID)
            && $body['order_id'] === $order->number
            && $body['amount'] === '100.00'
            && $body['currency'] === 'USD'
            && $body['url_callback'] === route('cryptomus.notification');
    });
});

it('lets the customer reopen the Cryptomus payment from the receipt', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);

    Http::fake([
        'api.cryptomus.com/*' => Http::response([
            'state' => 0,
            'result' => [
                'uuid' => 'invoice-uuid-456',
                'url' => 'https://pay.cryptomus.com/pay/invoice-uuid-456',
                'payment_status' => 'check',
            ],
        ], 200),
    ]);

    Livewire::actingAs($user)
        ->test(OrderReceipt::class, ['order' => $order])
        ->assertSee('Bayar Sekarang')
        ->call('pay')
        ->assertRedirect();

    $order->refresh();

    expect($order->provider_reference)->toBe('invoice-uuid-456');
});

it('completes a pending order when Cryptomus sends a verified paid notification', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'payment_amount' => '100.00',
        'is_final' => true,
        'status' => 'paid',
        'currency' => 'USD',
        'payer_currency' => 'USDT',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->provider_reference)->toBe('invoice-uuid-123')
        ->and($order->provider_status)->toBe('paid')
        ->and($order->payment_type)->toBe('USDT')
        ->and($order->licenses)->toHaveCount(1);
});

it('is idempotent when Cryptomus replays a paid notification', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'is_final' => true,
        'status' => 'paid',
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertOk();
    postJson('/cryptomus/notification', $payload)->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->licenses)->toHaveCount(1);
});

it('completes an overpaid notification', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'payment_amount' => '105.00',
        'is_final' => true,
        'status' => 'paid_over',
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Completed);
});

it('rejects a notification with an invalid signature', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'is_final' => true,
        'status' => 'paid',
        'currency' => 'USD',
        'sign' => 'forged-signature',
    ];

    postJson('/cryptomus/notification', $payload)->assertForbidden();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

it('cancels the order on fail, cancel, or system_fail notifications', function (string $status) {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'is_final' => true,
        'status' => $status,
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled);
})->with(['fail', 'cancel', 'system_fail']);

it('leaves the order pending on confirm_check notifications', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    $order = makeCryptomusOrder($user, [[$plan, 1]]);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'is_final' => false,
        'status' => 'confirm_check',
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

it('returns 404 for an unknown order', function () {
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => 'ORD-DOES-NOT-EXIST',
        'amount' => '100.00',
        'is_final' => true,
        'status' => 'paid',
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertNotFound();
});

it('ignores notifications for non-cryptomus orders', function () {
    $user = User::factory()->create();
    auth()->login($user);
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->manual()->create();
    seedCurrencies();
    $cart->add(Plan::factory()->priced(100)->create(), 1);
    $order = Order::checkout($cart, $method);
    $payload = [
        'uuid' => 'invoice-uuid-123',
        'order_id' => $order->number,
        'amount' => '100.00',
        'is_final' => true,
        'status' => 'paid',
        'currency' => 'USD',
    ];
    $payload['sign'] = cryptomusSignature($payload);

    postJson('/cryptomus/notification', $payload)->assertNotFound();

    expect($order->refresh()->status)->toBe(OrderStatus::AwaitingConfirmation);
});
