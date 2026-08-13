<?php

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\User;
use Livewire\Livewire;

it('lists customers for the admin', function () {
    $customer = User::factory()->create(['name' => 'John Doe']);

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('display_currency');
});

it('shows customer details to the admin', function () {
    $customer = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'display_currency' => 'idr',
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});
