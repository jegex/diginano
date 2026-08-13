<?php

use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Filament\Resources\Licenses\RelationManagers\ActivationsRelationManager;
use App\Models\Activation;
use App\Models\License;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;

it('shows the activations relation manager on the license view page', function () {
    $license = License::factory()->create();

    Livewire::test(ViewLicense::class, [
        'record' => $license->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSeeLivewire(ActivationsRelationManager::class);
});

it('can revoke an activation from the relation manager', function () {
    $license = License::factory()->create(['activation_limit' => 1]);
    $activation = $license->activateFor('shop.example.com');

    Livewire::test(ActivationsRelationManager::class, [
        'ownerRecord' => $license,
        'pageClass' => ViewLicense::class,
    ])
        ->assertCanSeeTableRecords([$activation])
        ->callAction(TestAction::make(DeleteAction::class)->table($activation));

    assertDatabaseCount(Activation::class, 0);
});
