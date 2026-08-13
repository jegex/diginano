<?php

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('charges package schemes per package of units in the cart', function () {
    $plan = Plan::factory()->package(15, 4)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 5);

    expect($cart->subtotalUsd())->toBe(30.0);
});

it('charges volume schemes at the rate of the reached tier in the cart', function () {
    $plan = Plan::factory()->volume([
        ['last_unit' => 10, 'unit_price' => 1000, 'fixed_fee' => null],
        ['last_unit' => null, 'unit_price' => 800, 'fixed_fee' => null],
    ])->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 20);

    expect($cart->subtotalUsd())->toBe(160.0);
});

it('charges graduated schemes per unit across each tier in the cart', function () {
    $plan = Plan::factory()->graduated([
        ['last_unit' => 10, 'unit_price' => 1000, 'fixed_fee' => 5000],
        ['last_unit' => null, 'unit_price' => 500, 'fixed_fee' => null],
    ])->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 20);

    expect($cart->subtotalUsd())->toBe(200.0);
});

it('defaults a pay-what-you-want amount to the suggested price', function () {
    $plan = Plan::factory()->pwyw(suggested: 20, min: 10)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect($cart->subtotalUsd())->toBe(20.0);
});

it('uses the custom amount for a pay-what-you-want plan', function () {
    $plan = Plan::factory()->pwyw(suggested: 20, min: 10)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);
    $cart->items->first()->update(['custom_amount' => 12.5]);

    expect($cart->subtotalUsd())->toBe(12.5);
});

it('rejects checkout when the pay-what-you-want amount is below the minimum', function () {
    seedCurrencies();

    $plan = Plan::factory()->pwyw(suggested: 20, min: 10)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);
    $cart->items->first()->update(['custom_amount' => 5]);
    $method = PaymentMethod::factory()->manual()->create();

    expect(fn () => Order::checkout($cart, $method))
        ->toThrow(HttpException::class);
});

it('completes a lead-magnet order for free and issues a license', function () {
    seedCurrencies();

    $user = User::factory()->create();
    $plan = Plan::factory()->leadMagnet()->create();
    $cart = Cart::for($user);
    $cart->add($plan, 1);

    expect($cart->totalUsd())->toBe(0.0);

    auth()->login($user);
    $order = Order::freeCheckout($cart);

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->total)->toBe(0.0)
        ->and($order->refresh()->licenses)->toHaveCount(1);
});

it('refuses a free checkout for a paid cart', function () {
    seedCurrencies();

    $plan = Plan::factory()->priced(25)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect(fn () => Order::freeCheckout($cart))
        ->toThrow(HttpException::class);
});

it('bills aggregated metered usage at renewal', function () {
    seedCurrencies();

    $user = User::factory()->create();
    $plan = Plan::factory()->usageBased()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $subscription->usageRecords()->createMany([
        ['user_id' => $user->id, 'plan_id' => $plan->id, 'quantity' => 100, 'recorded_at' => now()->subDay()],
        ['user_id' => $user->id, 'plan_id' => $plan->id, 'quantity' => 200, 'recorded_at' => now()],
    ]);
    $method = PaymentMethod::factory()->manual()->create();

    auth()->login($user);
    $order = Order::renewal($subscription, $method);

    expect($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(300)
        ->and($order->items->first()->line_total)->toBeGreaterThan(0.0);
});
