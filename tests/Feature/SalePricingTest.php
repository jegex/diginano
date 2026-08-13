<?php

use App\Livewire\ProductDetail;
use App\Models\Cart;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

it('uses the sale price when the sale is active', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);

    expect($plan->effectivePriceUsd())->toBe(80.0);
    expect($plan->isOnSale())->toBeTrue();
});

it('uses the base price when there is no sale', function () {
    $plan = Plan::factory()->create(['price' => 100]);

    expect($plan->effectivePriceUsd())->toBe(100.0);
    expect($plan->isOnSale())->toBeFalse();
});

it('does not apply a sale that has not started', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->addDay(),
    ]);

    expect($plan->isOnSale())->toBeFalse();
    expect($plan->effectivePriceUsd())->toBe(100.0);
});

it('does not apply a sale that has ended', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->subDays(2),
        'sale_ends_at' => now()->subDay(),
    ]);

    expect($plan->isOnSale())->toBeFalse();
    expect($plan->effectivePriceUsd())->toBe(100.0);
});

it('applies a sale that has started but has no end date', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->subDay(),
    ]);

    expect($plan->isOnSale())->toBeTrue();
});

it('applies a sale with an end date but no start date', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_ends_at' => now()->addDay(),
    ]);

    expect($plan->isOnSale())->toBeTrue();
});

it('uses the sale price in the cart subtotal without a code', function () {
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);
    $cart = Cart::for(User::factory()->create());
    $cart->add($plan, 2);

    expect($cart->subtotalUsd())->toBe(160.0);
});

it('shows the sale price on the product page', function () {
    seedCurrencies();
    $plan = Plan::factory()->create([
        'price' => 100,
        'sale_price' => 80,
        'sale_starts_at' => now()->subDay(),
        'sale_ends_at' => now()->addDay(),
    ]);

    Livewire::test(ProductDetail::class, ['product' => $plan->product])
        ->assertSee('$80.00');
});
