<?php

use App\Enums\CouponType;
use App\Livewire\CartPage;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

it('applies a global percentage coupon to the order total', function () {
    $coupon = Coupon::factory()->percentage()->create(['value' => 10]);
    $plan = Plan::factory()->priced(100)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 2);

    expect($cart->totalUsd($coupon))->toBe(180.0);
});

it('applies a global fixed coupon to the order total', function () {
    $coupon = Coupon::factory()->fixed()->create(['fixed_value' => 25]);
    $plan = Plan::factory()->priced(100)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect($cart->totalUsd($coupon))->toBe(75.0);
});

it('does not let a fixed coupon exceed the eligible subtotal', function () {
    $coupon = Coupon::factory()->fixed()->create(['fixed_value' => 500]);
    $plan = Plan::factory()->priced(100)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect($cart->couponDiscountUsd($coupon))->toBe(100.0);
    expect($cart->totalUsd($coupon))->toBe(0.0);
});

it('applies a product-scoped coupon only to eligible items', function () {
    $eligibleProduct = Product::factory()->create();
    $plan = Plan::factory()->priced(100)->create(['product_id' => $eligibleProduct->id]);
    $other = Plan::factory()->priced(100)->create();
    $coupon = Coupon::factory()->percentage()->create(['value' => 10]);
    $coupon->products()->attach($eligibleProduct);

    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);
    $cart->add($other, 1);

    expect($cart->eligibleSubtotalUsd($coupon))->toBe(100.0);
    expect($cart->couponDiscountUsd($coupon))->toBe(10.0);
    expect($cart->totalUsd($coupon))->toBe(190.0);
});

it('applies no discount when a product-scoped coupon matches nothing', function () {
    $coupon = Coupon::factory()->percentage()->create(['value' => 10]);
    $coupon->products()->attach(Product::factory()->create());
    $plan = Plan::factory()->priced(100)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect($cart->couponDiscountUsd($coupon))->toBe(0.0);
    expect($cart->totalUsd($coupon))->toBe(100.0);
});

it('returns the plain subtotal when no coupon is applied', function () {
    $plan = Plan::factory()->priced(100)->create();
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 2);

    expect($cart->totalUsd())->toBe(200.0);
});

it('uppercases coupon codes on save', function () {
    $coupon = Coupon::create([
        'code' => 'diskon10',
        'type' => CouponType::Percentage,
        'value' => 10,
    ]);

    expect($coupon->code)->toBe('DISKON10');
});

it('tracks a single-use coupon as used by a customer', function () {
    $user = User::factory()->create();
    $coupon = Coupon::factory()->singleUse()->create();

    $coupon->usages()->create(['user_id' => $user->id]);

    expect($coupon->usages()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('applies a coupon to the plan subtotal but not the setup fee', function () {
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(100)->setupFee(20))->create();
    $coupon = Coupon::factory()->percentage()->create(['code' => 'HELLO10', 'value' => 10]);
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 1);

    expect($cart->totalUsd($coupon))->toBe(110.0);
});

it('applies a coupon on the cart page', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    Cart::for($user)->add($plan, 1);
    Coupon::factory()->percentage()->create(['code' => 'HELLO10', 'value' => 10]);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('couponCode', 'hello10')
        ->call('applyCoupon')
        ->assertHasNoErrors()
        ->assertSee('HELLO10')
        ->assertSee('$10.00')
        ->assertSee('$90.00');
});

it('rejects an unknown coupon code on the cart page', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('couponCode', 'NOPE')
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode']);
});

it('lets a customer remove an applied coupon', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(100)->create();
    Cart::for($user)->add($plan, 1);
    Coupon::factory()->percentage()->create(['code' => 'HELLO10', 'value' => 10]);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('couponCode', 'HELLO10')
        ->call('applyCoupon')
        ->assertSee('$90.00')
        ->call('removeCoupon')
        ->assertDontSee('$90.00')
        ->assertSee('$100.00');
});

it('applies a product-scoped coupon only to eligible items in the cart', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $eligibleProduct = Product::factory()->create();
    $eligible = Plan::factory()->priced(100)->create(['product_id' => $eligibleProduct->id]);
    $other = Plan::factory()->priced(100)->create();
    $coupon = Coupon::factory()->percentage()->create(['code' => 'SCOPED10', 'value' => 10]);
    $coupon->products()->attach($eligibleProduct);
    $cart = Cart::for($user);
    $cart->add($eligible, 1);
    $cart->add($other, 1);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('couponCode', 'SCOPED10')
        ->call('applyCoupon')
        ->assertSee('$10.00')
        ->assertSee('$190.00');
});

it('does not let guests apply a coupon', function () {
    Livewire::test(CartPage::class)
        ->call('applyCoupon')
        ->assertForbidden();
});
