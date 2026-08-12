<?php

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\OrderStatus;
use Livewire\Livewire;

it('lists orders for the admin', function () {
    $order = Order::factory()->create(['total_usd' => 123.45]);

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertTableColumnExists('number')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('total_usd');
});

it('filters orders by status', function () {
    $pending = Order::factory()->create(['status' => OrderStatus::Pending]);
    $completed = Order::factory()->create(['status' => OrderStatus::Completed]);

    Livewire::test(ListOrders::class)
        ->filterTable('status', OrderStatus::Pending->value)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$completed]);
});

it('shows order details to the admin', function () {
    $plan = Plan::factory()->create(['price' => 50]);
    $order = Order::factory()->create(['total_usd' => 100]);
    OrderItem::factory()->forPlan($plan)->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'line_total_usd' => 100,
    ]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSee($order->number)
        ->assertSee($order->user->name)
        ->assertSee($plan->name);
});

it('lets the admin see the customer name on the order list', function () {
    $order = Order::factory()->create();

    Livewire::test(ListOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->assertTableColumnExists('user.name');
});
