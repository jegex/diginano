<?php

use App\Enums\OrderStatus;
use App\Models\Order;

use function Pest\Laravel\artisan;

it('expires pending orders older than 24 hours', function () {
    $old = Order::factory()->create(['created_at' => now()->subHours(25)]);
    $fresh = Order::factory()->create(['created_at' => now()->subHours(1)]);

    artisan('orders:expire')
        ->expectsOutputToContain('Expired 1 order(s)')
        ->assertSuccessful();

    expect($old->fresh()->status)->toBe(OrderStatus::Expired);
    expect($fresh->fresh()->status)->toBe(OrderStatus::Pending);
});

it('does not expire orders that are not pending', function () {
    $old = Order::factory()->completed()->create(['created_at' => now()->subHours(48)]);

    artisan('orders:expire')->assertSuccessful();

    expect($old->fresh()->status)->toBe(OrderStatus::Completed);
});

it('expires nothing when no old pending orders exist', function () {
    Order::factory()->create(['created_at' => now()->subHours(2)]);

    artisan('orders:expire')
        ->expectsOutputToContain('Expired 0 order(s)')
        ->assertSuccessful();
});
