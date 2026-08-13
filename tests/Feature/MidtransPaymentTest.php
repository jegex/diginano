<?php

use App\DisplayCurrency;
use App\Livewire\CheckoutPage;
use App\Livewire\OrderReceipt;
use App\Models\Cart;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\post;

const MIDTRANS_SERVER_KEY = 'SB-Mid-server-key';

function makeMidtransOrder(User $user, array $plans = []): Order
{
    auth()->login($user);
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->midtrans()->create();
    ExchangeRate::firstOrCreate(['currency' => DisplayCurrency::Idr], ['rate' => 15000]);

    foreach ($plans as [$plan, $qty]) {
        $cart->add($plan, $qty);
    }

    return Order::checkout($cart, $method);
}

function midtransSignature(string $orderId, string $statusCode, string $grossAmount): string
{
    return hash('sha512', $orderId.$statusCode.$grossAmount.MIDTRANS_SERVER_KEY);
}

it('redirects to the Snap hosted page after checkout and stores the token', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $method = PaymentMethod::factory()->midtrans()->create();
    ExchangeRate::factory()->create(['currency' => DisplayCurrency::Idr, 'rate' => 15000]);
    Cart::for($user)->add($plan, 1);

    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/transactions/xyz',
        ], 201),
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->isMidtransPayment())->toBeTrue()
        ->and($order->settlement_currency)->toBe(DisplayCurrency::Idr)
        ->and($order->snap_token)->toBe('snap-token')
        ->and($order->snap_redirect_url)->toBe('https://app.sandbox.midtrans.com/snap/v4/transactions/xyz');

    Http::assertSent(function (Request $request) use ($order): bool {
        $payload = $request->data();

        return $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            && $payload['transaction_details']['order_id'] === $order->number
            && $payload['transaction_details']['gross_amount'] === 1500000
            && $request->hasHeader('Authorization', 'Basic '.base64_encode(MIDTRANS_SERVER_KEY.':'));
    });
});

it('lets the customer reopen the Snap checkout from the receipt', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $order = makeMidtransOrder($user, [[$plan, 1]]);

    Http::fake([
        'app.sandbox.midtrans.com/*' => Http::response([
            'token' => 'snap-token-2',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/transactions/abc',
        ], 201),
    ]);

    Livewire::actingAs($user)
        ->test(OrderReceipt::class, ['order' => $order])
        ->assertSee('Bayar Sekarang')
        ->call('pay')
        ->assertRedirect();

    $order->refresh();

    expect($order->snap_token)->toBe('snap-token-2');
});

it('completes a pending order when Midtrans sends a verified settlement notification', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100, 'licenses_per_unit' => 2]);
    $order = makeMidtransOrder($user, [[$plan, 1]]);
    $payload = [
        'order_id' => $order->number,
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-123',
        'payment_type' => 'gopay',
        'signature_key' => midtransSignature($order->number, '200', '1500000.00'),
    ];

    post('/midtrans/notification', $payload)->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->provider_reference)->toBe('txn-123')
        ->and($order->provider_status)->toBe('settlement')
        ->and($order->payment_type)->toBe('gopay')
        ->and($order->licenses)->toHaveCount(2);
});

it('is idempotent when Midtrans replays a settlement notification', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100, 'licenses_per_unit' => 2]);
    $order = makeMidtransOrder($user, [[$plan, 1]]);
    $payload = [
        'order_id' => $order->number,
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-123',
        'payment_type' => 'gopay',
        'signature_key' => midtransSignature($order->number, '200', '1500000.00'),
    ];

    post('/midtrans/notification', $payload)->assertOk();
    post('/midtrans/notification', $payload)->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->licenses)->toHaveCount(2);
});

it('rejects a notification with an invalid signature', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $order = makeMidtransOrder($user, [[$plan, 1]]);
    $payload = [
        'order_id' => $order->number,
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-123',
        'signature_key' => 'forged-signature',
    ];

    post('/midtrans/notification', $payload)->assertForbidden();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

it('completes a capture with fraud status accept but leaves challenge pending', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);

    $acceptedOrder = makeMidtransOrder($user, [[$plan, 1]]);
    $accepted = [
        'order_id' => $acceptedOrder->number,
        'transaction_status' => 'capture',
        'fraud_status' => 'accept',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-accept',
        'signature_key' => midtransSignature($acceptedOrder->number, '200', '1500000.00'),
    ];
    post('/midtrans/notification', $accepted)->assertOk();

    expect($acceptedOrder->refresh()->status)->toBe(OrderStatus::Completed);

    $challengedOrder = makeMidtransOrder($user, [[$plan, 1]]);
    $challenge = [
        'order_id' => $challengedOrder->number,
        'transaction_status' => 'capture',
        'fraud_status' => 'challenge',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-challenge',
        'signature_key' => midtransSignature($challengedOrder->number, '200', '1500000.00'),
    ];
    post('/midtrans/notification', $challenge)->assertOk();

    expect($challengedOrder->refresh()->status)->toBe(OrderStatus::Pending);
});

it('cancels the order on deny, cancel, expire, or failure notifications', function (string $status) {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $order = makeMidtransOrder($user, [[$plan, 1]]);
    $payload = [
        'order_id' => $order->number,
        'transaction_status' => $status,
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-'.$status,
        'signature_key' => midtransSignature($order->number, '200', '1500000.00'),
    ];

    post('/midtrans/notification', $payload)->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled);
})->with(['deny', 'cancel', 'expire', 'failure']);

it('returns 404 for an unknown order', function () {
    $payload = [
        'order_id' => 'ORD-DOES-NOT-EXIST',
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '1000.00',
        'transaction_id' => 'txn-unknown',
        'signature_key' => midtransSignature('ORD-DOES-NOT-EXIST', '200', '1000.00'),
    ];

    post('/midtrans/notification', $payload)->assertNotFound();
});

it('ignores notifications for non-midtrans orders', function () {
    $user = User::factory()->create();
    auth()->login($user);
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->manual()->create();
    ExchangeRate::factory()->create(['currency' => DisplayCurrency::Idr, 'rate' => 15000]);
    $cart->add(Plan::factory()->create(['price' => 100]), 1);
    $order = Order::checkout($cart, $method);
    $payload = [
        'order_id' => $order->number,
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => '1500000.00',
        'transaction_id' => 'txn-123',
        'signature_key' => midtransSignature($order->number, '200', '1500000.00'),
    ];

    post('/midtrans/notification', $payload)->assertNotFound();

    expect($order->refresh()->status)->toBe(OrderStatus::AwaitingConfirmation);
});
