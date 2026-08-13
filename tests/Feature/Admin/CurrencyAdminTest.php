<?php

use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\EditCurrency;
use App\Models\Currency;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create a currency', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'idr',
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 16000,
            'decimal_places' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Currency::class, [
        'code' => 'idr',
        'exchange_rate' => '16000.000000',
    ]);
});

it('requires a code', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => null,
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 16000,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => ['required']]);
});

it('requires a positive rate', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'idr',
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['exchange_rate' => ['min']]);
});

it('requires a unique code', function () {
    Currency::factory()->idr()->create();

    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'idr',
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 16000,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => ['unique']]);
});

it('can edit a currency', function () {
    $currency = Currency::factory()->idr()->create(['exchange_rate' => 16000]);

    Livewire::test(EditCurrency::class, [
        'record' => $currency->getRouteKey(),
    ])
        ->fillForm([
            'code' => 'idr',
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 16500,
            'decimal_places' => 0,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Currency::class, [
        'id' => $currency->id,
        'exchange_rate' => '16500.000000',
    ]);
});
