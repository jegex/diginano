<?php

use App\Enums\OrderStatus;
use App\Livewire\OrderReceipt;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderProof;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use App\Services\OrderFinalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;

function makeManualOrder(User $user): Order
{
    auth()->login($user);
    $cart = Cart::for($user);
    $plan = Plan::factory()->priced(100)->create();
    $method = PaymentMethod::factory()->manual()->create();
    seedCurrencies();
    $cart->add($plan, 1);

    return Order::checkout($cart, $method);
}

it('creates a manual order as awaiting confirmation', function () {
    $user = User::factory()->create();

    $order = makeManualOrder($user);

    expect($order->status)->toBe(OrderStatus::AwaitingConfirmation)
        ->and($order->settlement_currency)->toBe('idr')
        ->and($order->settlementAmount())->toBe(1500000.0);
});

it('shows bank details and the payable amount on the manual receipt', function () {
    $user = User::factory()->create();
    $order = makeManualOrder($user);
    $method = $order->paymentMethod;

    actingAs($user);

    get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Transfer ke rekening berikut')
        ->assertSee($method->config['bank_name'])
        ->assertSee($method->config['account_name'])
        ->assertSee($method->config['account_number'])
        ->assertSee('1.500.000')
        ->assertSee('Upload bukti pembayaran');
});

it('does not show bank details on a paid manual order', function () {
    $user = User::factory()->create();
    $order = makeManualOrder($user);
    $order->update(['status' => OrderStatus::Completed]);

    actingAs($user);

    get(route('orders.show', $order))
        ->assertOk()
        ->assertDontSee('Transfer ke rekening berikut')
        ->assertDontSee('Upload bukti pembayaran');
});

it('lets the owner upload a payment proof', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $order = makeManualOrder($user);

    Livewire::actingAs($user)
        ->test(OrderReceipt::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->image('bukti.png'))
        ->call('submitProof')
        ->assertHasNoErrors();

    $proof = $order->proofs()->firstOrFail();

    expect($proof->original_name)->toBe('bukti.png')
        ->and($proof->submitted_at)->not->toBeNull();

    assertDatabaseCount(OrderProof::class, 1);
    Storage::disk('public')->assertExists($proof->file_path);
});

it('rejects non-image proof uploads', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $order = makeManualOrder($user);

    Livewire::actingAs($user)
        ->test(OrderReceipt::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->create('bukti.exe', 50))
        ->call('submitProof')
        ->assertHasErrors(['proof' => 'mimes']);

    assertDatabaseCount(OrderProof::class, 0);
});

it('forbids uploading proof on a completed order', function () {
    $user = User::factory()->create();
    $order = makeManualOrder($user);
    $order->update(['status' => OrderStatus::Completed]);

    Livewire::actingAs($user)
        ->test(OrderReceipt::class, ['order' => $order])
        ->call('submitProof')
        ->assertForbidden();
});

it('completes the order when an admin approves the proof', function () {
    $user = User::factory()->create();
    $order = makeManualOrder($user);
    $plan = $order->items()->first()->plan;

    $order->proofs()->create([
        'file_path' => 'proofs/bukti.png',
        'original_name' => 'bukti.png',
        'submitted_at' => now(),
    ]);

    app(OrderFinalizer::class)->finalize($order);
    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->completed_at)->not->toBeNull()
        ->and($order->licenses)->toHaveCount(1);
});

it('cancels the order without licenses when an admin rejects the proof', function () {
    $user = User::factory()->create();
    $order = makeManualOrder($user);

    $order->update(['status' => OrderStatus::Cancelled]);
    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->licenses)->toBeEmpty();
});
