<?php

use App\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Models\PaymentMethod;
use App\PaymentMethodType;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create a manual bank transfer payment method', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'type' => PaymentMethodType::Manual->value,
            'name' => 'Bank Transfer (BNI)',
            'is_enabled' => true,
            'config.bank_name' => 'BNI',
            'config.account_name' => 'Diginano Store',
            'config.account_number' => '9876543210',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(PaymentMethod::class, [
        'name' => 'Bank Transfer (BNI)',
        'type' => PaymentMethodType::Manual->value,
        'is_enabled' => true,
    ]);

    $method = PaymentMethod::where('name', 'Bank Transfer (BNI)')->firstOrFail();

    expect($method->config)->toBe([
        'bank_name' => 'BNI',
        'account_name' => 'Diginano Store',
        'account_number' => '9876543210',
    ]);
});

it('can create a midtrans payment method', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'type' => PaymentMethodType::Midtrans->value,
            'name' => 'Midtrans',
            'is_enabled' => true,
            'config.server_key' => 'SB-Mid-server-key',
            'config.client_key' => 'SB-Mid-client-key',
            'config.is_sandbox' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $method = PaymentMethod::where('name', 'Midtrans')->firstOrFail();

    expect($method->config)->toBe([
        'server_key' => 'SB-Mid-server-key',
        'client_key' => 'SB-Mid-client-key',
        'is_sandbox' => true,
    ]);
});

it('requires a name and type', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'type' => null,
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['type' => ['required'], 'name' => ['required']]);
});

it('can edit a payment method', function () {
    $method = PaymentMethod::factory()->create();

    Livewire::test(EditPaymentMethod::class, ['record' => $method->getRouteKey()])
        ->fillForm([
            'type' => PaymentMethodType::Manual->value,
            'name' => 'Bank Transfer (BCA)',
            'is_enabled' => false,
            'config.bank_name' => 'BCA',
            'config.account_name' => 'Diginano Store',
            'config.account_number' => '1234567890',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(PaymentMethod::class, [
        'id' => $method->id,
        'name' => 'Bank Transfer (BCA)',
        'is_enabled' => false,
    ]);
});

it('lists payment methods for the admin', function () {
    $method = PaymentMethod::factory()->create(['name' => 'BCA']);

    Livewire::test(ListPaymentMethods::class)
        ->assertCanSeeTableRecords([$method])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('type')
        ->assertTableColumnExists('is_enabled');
});

it('can toggle a payment method enabled state from the table', function () {
    $method = PaymentMethod::factory()->create(['is_enabled' => true]);

    Livewire::test(ListPaymentMethods::class)
        ->call('updateTableColumnState', 'is_enabled', $method->getRouteKey(), false);

    expect($method->fresh()->is_enabled)->toBeFalse();
});
