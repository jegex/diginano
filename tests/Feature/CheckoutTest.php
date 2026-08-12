<?php

use App\DisplayCurrency;
use App\Livewire\CheckoutPage;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('prompts a guest to log in before checking out', function () {
    Livewire::test(CheckoutPage::class)
        ->assertSee('Masuk untuk melanjutkan checkout');
});

it('lets a customer place an order from their cart', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $method = PaymentMethod::factory()->create();
    Cart::for($user)->add($plan, 2);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->subtotal_usd)->toBe('200.00')
        ->and($order->discount_usd)->toBe('0.00')
        ->and($order->total_usd)->toBe('200.00')
        ->and($order->currency)->toBe(DisplayCurrency::Usd)
        ->and($order->payment_method_id)->toBe($method->id);

    assertDatabaseHas(OrderItem::class, [
        'order_id' => $order->id,
        'plan_id' => $plan->id,
        'quantity' => 2,
        'unit_price_usd' => '100.00',
        'line_total_usd' => '200.00',
    ]);

    assertDatabaseCount(CartItem::class, 0);
});

it('applies a coupon from the session to the order', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100]);
    $coupon = Coupon::factory()->percentage()->create(['value' => 10]);
    $method = PaymentMethod::factory()->create();
    Cart::for($user)->add($plan, 1);
    Session::put('applied_coupon_code', $coupon->code);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertHasNoErrors();

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($order->coupon_id)->toBe($coupon->id)
        ->and($order->discount_usd)->toBe('10.00')
        ->and($order->total_usd)->toBe('90.00');

    expect(Session::get('applied_coupon_code'))->toBeNull();
});

it('stores the display currency and exchange rate on the order', function () {
    $user = User::factory()->create(['display_currency' => DisplayCurrency::Idr]);
    $plan = Plan::factory()->create(['price' => 100]);
    $method = PaymentMethod::factory()->create();
    ExchangeRate::factory()->create(['currency' => DisplayCurrency::Idr, 'rate' => 15000]);
    Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertHasNoErrors();

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect($order->currency)->toBe(DisplayCurrency::Idr)
        ->and($order->exchange_rate)->toBe('15000.000000')
        ->and($order->total_usd)->toBe('100.00');
});

it('requires selecting an enabled payment method', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $disabled = PaymentMethod::factory()->disabled()->create();
    Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $disabled->id)
        ->call('checkout')
        ->assertHasErrors(['paymentMethodId' => 'exists']);
});

it('requires a payment method', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', null)
        ->call('checkout')
        ->assertHasErrors(['paymentMethodId' => 'required']);
});

it('sends a guest with an empty cart back to the cart page', function () {
    $user = User::factory()->create();
    $method = PaymentMethod::factory()->create();

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->set('paymentMethodId', $method->id)
        ->call('checkout')
        ->assertRedirect(route('cart'));
});
