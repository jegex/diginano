<?php

use App\Livewire\CartPage;
use App\Livewire\ProductDetail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(fn () => seedCurrencies());

it('lets a customer add a plan with quantity to their cart', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    Livewire::actingAs($user)
        ->test(ProductDetail::class, ['product' => $plan->product])
        ->set("quantities.{$plan->id}", 3)
        ->call('addToCart', $plan->id)
        ->assertRedirect(route('cart'));

    assertDatabaseHas(Cart::class, ['user_id' => $user->id]);
    assertDatabaseHas(CartItem::class, [
        'plan_id' => $plan->id,
        'quantity' => 3,
    ]);
});

it('increments the quantity when the same plan is added again', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    Cart::for($user)->add($plan, 2);

    Livewire::actingAs($user)
        ->test(ProductDetail::class, ['product' => $plan->product])
        ->call('addToCart', $plan->id)
        ->assertRedirect(route('cart'));

    assertDatabaseHas(CartItem::class, [
        'plan_id' => $plan->id,
        'quantity' => 3,
    ]);
});

it('lets a customer change the quantity of an item', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $item = Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->set("quantities.{$item->id}", 4)
        ->call('updateQuantity', $item->id)
        ->assertHasNoErrors();

    expect($item->fresh()->quantity)->toBe(4);
});

it('lets a customer remove an item from the cart', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $item = Cart::for($user)->add($plan, 1);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->call('removeItem', $item->id)
        ->assertHasNoErrors();

    assertDatabaseMissing(CartItem::class, ['id' => $item->id]);
});

it('shows items that were added in a previous request', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['name' => 'Lifetime', 'price' => 50]);
    Cart::for($user)->add($plan, 2);

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->assertSee('Lifetime')
        ->assertSee('2');
});

it('keeps carts separate between customers', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $plan = Plan::factory()->create();
    Cart::for($userA)->add($plan, 1);

    expect(Cart::for($userB)->isEmpty())->toBeTrue();
});

it('redirects guests to login when adding to cart', function () {
    $plan = Plan::factory()->create();

    Livewire::test(ProductDetail::class, ['product' => $plan->product])
        ->call('addToCart', $plan->id)
        ->assertRedirect(route('filament.admin.auth.login'));

    assertDatabaseCount(CartItem::class, 0);
});

it('prompts guests to log in on the cart page', function () {
    Livewire::test(CartPage::class)
        ->assertSee('Masuk untuk melihat keranjang Anda')
        ->assertSee(route('filament.admin.auth.login'));
});

it('prevents a customer from changing another customers item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $plan = Plan::factory()->create();
    $item = Cart::for($owner)->add($plan, 1);

    Livewire::actingAs($intruder)
        ->test(CartPage::class)
        ->set("quantities.{$item->id}", 9)
        ->call('updateQuantity', $item->id)
        ->assertForbidden();

    expect($item->fresh()->quantity)->toBe(1);
});

it('prevents a customer from removing another customers item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $plan = Plan::factory()->create();
    $item = Cart::for($owner)->add($plan, 1);

    Livewire::actingAs($intruder)
        ->test(CartPage::class)
        ->call('removeItem', $item->id)
        ->assertForbidden();

    assertDatabaseHas(CartItem::class, ['id' => $item->id]);
});

it('shows an empty state for an empty cart', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CartPage::class)
        ->assertSee('Keranjang kosong');
});
