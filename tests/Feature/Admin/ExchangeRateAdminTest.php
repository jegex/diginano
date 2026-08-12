<?php

use App\Filament\Resources\ExchangeRates\Pages\CreateExchangeRate;
use App\Filament\Resources\ExchangeRates\Pages\EditExchangeRate;
use App\Models\ExchangeRate;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create an exchange rate', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'currency' => 'idr',
            'rate' => 16000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(ExchangeRate::class, [
        'currency' => 'idr',
        'rate' => '16000.000000',
    ]);
});

it('requires a currency', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'currency' => null,
            'rate' => 16000,
        ])
        ->call('create')
        ->assertHasFormErrors(['currency' => ['required']]);
});

it('requires a positive rate', function () {
    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'currency' => 'idr',
            'rate' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['rate' => ['min']]);
});

it('requires a unique currency', function () {
    ExchangeRate::factory()->create(['currency' => 'idr']);

    Livewire::test(CreateExchangeRate::class)
        ->fillForm([
            'currency' => 'idr',
            'rate' => 16000,
        ])
        ->call('create')
        ->assertHasFormErrors(['currency' => ['unique']]);
});

it('can edit an exchange rate', function () {
    $rate = ExchangeRate::factory()->create(['currency' => 'idr', 'rate' => 16000]);

    Livewire::test(EditExchangeRate::class, [
        'record' => $rate->getRouteKey(),
    ])
        ->fillForm([
            'currency' => 'idr',
            'rate' => 16500,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(ExchangeRate::class, [
        'id' => $rate->id,
        'rate' => '16500.000000',
    ]);
});
