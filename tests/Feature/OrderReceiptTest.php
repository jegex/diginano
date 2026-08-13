<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\User;

use function Pest\Laravel\get;

it('lets the order owner view their receipt', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $plan = Plan::factory()->priced(50)->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Completed,
        'currency' => 'usd',
    ]);
    OrderItem::factory()->forPlan($plan)->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'line_total' => 100,
    ]);

    $this->actingAs($user);

    get(route('orders.show', $order))
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee($plan->name)
        ->assertSee('100');
});

it('forbids other users from viewing the receipt', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other);

    get(route('orders.show', $order))->assertForbidden();
});

it('forbids guests from viewing the receipt', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    get(route('orders.show', $order))->assertForbidden();
});

it('shows the pending payment reminder on an unpaid order', function () {
    seedCurrencies();
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Pending,
    ]);

    $this->actingAs($user);

    get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Pesanan ini belum dibayar.');
});
