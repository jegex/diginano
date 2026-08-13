<?php

use App\Livewire\CartPage;
use App\Livewire\ProductDetail;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\User;
use DomainException;
use Livewire\Livewire;

it('shows the default USD price on the product page', function () {
    seedCurrencies();
    $plan = Plan::factory()->priced(99.5)->create();

    Livewire::test(ProductDetail::class, ['product' => $plan->product])
        ->assertSee('$99.50');
});

it('converts the product price to the customers display currency', function () {
    $user = User::factory()->create(['display_currency' => 'idr']);
    seedCurrencies(['idr' => 16000]);
    $plan = Plan::factory()->priced(100)->create();

    Livewire::actingAs($user)
        ->test(ProductDetail::class, ['product' => $plan->product])
        ->assertSee('Rp 1.600.000');
});

it('shows the cart subtotal in the customers display currency', function () {
    $user = User::factory()->create(['display_currency' => 'eur']);
    seedCurrencies(['eur' => 0.9]);
    $plan = Plan::factory()->priced(100)->create();
    Cart::for($user)->add($plan, 2);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->assertSee('€180.00');
});

it('lets a customer choose their display currency', function () {
    $user = User::factory()->create();
    seedCurrencies(['idr' => 16000]);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('currency', 'idr')
        ->call('changeCurrency')
        ->assertHasNoErrors();

    expect($user->fresh()->display_currency)->toBe('idr');
});

it('re-renders the subtotal in the newly chosen currency', function () {
    $user = User::factory()->create();
    seedCurrencies(['idr' => 16000]);
    $plan = Plan::factory()->priced(50)->create();
    Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('currency', 'idr')
        ->call('changeCurrency')
        ->assertSee('Rp 800.000');
});

it('rejects an invalid display currency', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set('currency', 'jpy')
        ->call('changeCurrency')
        ->assertHasErrors(['currency']);
});

it('fails loudly when no exchange rate is configured for a chosen currency', function () {
    expect(fn () => Currency::required('eur')->convertUsd(10))
        ->toThrow(DomainException::class);
});
