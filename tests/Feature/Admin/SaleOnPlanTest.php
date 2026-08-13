<?php

use App\Enums\PlanPricing;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\PlansRelationManager;
use App\Models\Plan;
use App\Models\Product;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create a plan with an active sale', function () {
    $product = Product::factory()->create();

    Livewire::test(PlansRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create')
        ->assertTableActionHalted('create')
        ->fillForm([
            'name' => 'Pro',
            'pricing_mode' => PlanPricing::OneTime->value,
            'price' => 100,
            'licenses_per_unit' => 1,
            'sale_price' => 80,
            'sale_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'sale_ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    assertDatabaseHas(Plan::class, [
        'product_id' => $product->id,
        'name' => 'Pro',
        'sale_price' => 8000,
    ]);
});

it('rejects a sale that ends before it starts', function () {
    $product = Product::factory()->create();

    Livewire::test(PlansRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create')
        ->assertTableActionHalted('create')
        ->fillForm([
            'name' => 'Pro',
            'pricing_mode' => PlanPricing::OneTime->value,
            'price' => 100,
            'licenses_per_unit' => 1,
            'sale_price' => 80,
            'sale_starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'sale_ends_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ])
        ->callMountedTableAction()
        ->assertHasTableActionErrors(['sale_ends_at']);
});

it('can update a sale from the relation manager', function () {
    $product = Product::factory()->create();
    $plan = Plan::factory()->create([
        'product_id' => $product->id,
        'price' => 100,
        'sale_price' => 80,
    ]);

    Livewire::test(PlansRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertCanSeeTableRecords([$plan])
        ->assertTableActionExists('edit', record: $plan)
        ->mountTableAction('edit', $plan)
        ->fillForm([
            'sale_price' => 75,
            'sale_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'sale_ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($plan->fresh()->sale_price)->toBe(75.0);
});
