<?php

use App\Filament\Resources\ProductReleases\Pages\CreateProductRelease;
use App\Filament\Resources\ProductReleases\Pages\EditProductRelease;
use App\Models\Product;
use App\Models\ProductRelease;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can create a product release with a changelog', function () {
    $product = Product::factory()->create();
    $release = ProductRelease::factory()->make();

    Livewire::test(CreateProductRelease::class)
        ->fillForm([
            'product_id' => $product->id,
            'version' => $release->version,
            'changelog' => $release->changelog,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(ProductRelease::class, [
        'product_id' => $product->id,
        'version' => $release->version,
        'changelog' => $release->changelog,
    ]);
});

it('requires a version for a product release', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateProductRelease::class)
        ->fillForm([
            'product_id' => $product->id,
            'version' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['version' => ['required']]);
});

it('can edit a product release changelog', function () {
    $release = ProductRelease::factory()->create(['changelog' => 'Old notes']);

    Livewire::test(EditProductRelease::class, [
        'record' => $release->getRouteKey(),
    ])
        ->fillForm([
            'changelog' => 'New notes',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(ProductRelease::class, [
        'id' => $release->id,
        'changelog' => 'New notes',
    ]);
});

it('can delete a product release', function () {
    $release = ProductRelease::factory()->create();

    Livewire::test(EditProductRelease::class, [
        'record' => $release->getRouteKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing(ProductRelease::class, ['id' => $release->id]);
});
