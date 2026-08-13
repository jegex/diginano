<?php

use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\RenewalIntervalUnit;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\PlansRelationManager;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can create a product', function () {
    $data = Product::factory()->make([
        'is_published' => true,
    ]);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => $data->name,
            'description' => $data->description,
            'is_published' => $data->is_published,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Product::class, [
        'name' => $data->name,
        'slug' => Str::slug($data->name),
        'is_published' => 1,
    ]);
});

it('requires a product name', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => ['required']]);
});

it('shows the plans relation manager on the edit page', function () {
    $product = Product::factory()->create();

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSeeLivewire(PlansRelationManager::class);
});

it('can create a one-time plan through the relation manager', function () {
    $product = Product::factory()->create();

    mountPlanCreateAction($product)
        ->fillForm([
            'name' => 'Lifetime',
            'has_license_keys' => true,
            'license_activation_limit' => 2,
            'price' => [
                'category' => PriceCategory::OneTime->value,
                'scheme' => PricingScheme::Standard->value,
                'unit_price' => 49.5,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Plan::class, [
        'product_id' => $product->id,
        'name' => 'Lifetime',
    ]);

    assertDatabaseHas(Price::class, [
        'category' => PriceCategory::OneTime,
        'scheme' => PricingScheme::Standard,
        'unit_price' => 4950,
    ]);
});

it('can create a subscription plan through the relation manager', function () {
    $product = Product::factory()->create();

    mountPlanCreateAction($product)
        ->fillForm([
            'name' => 'Pro',
            'license_activation_limit' => 1,
            'price' => [
                'category' => PriceCategory::Subscription->value,
                'scheme' => PricingScheme::Standard->value,
                'unit_price' => 9.5,
                'renewal_interval_unit' => RenewalIntervalUnit::Month->value,
                'renewal_interval_quantity' => 1,
            ],
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Price::class, [
        'category' => PriceCategory::Subscription,
        'scheme' => PricingScheme::Standard,
        'unit_price' => 950,
        'renewal_interval_unit' => RenewalIntervalUnit::Month,
        'renewal_interval_quantity' => 1,
    ]);
});

it('can edit a product name and unpublish it', function () {
    $product = Product::factory()->create(['name' => 'Old Name', 'is_published' => true]);

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'New Name',
            'is_published' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Product::class, [
        'id' => $product->id,
        'name' => 'New Name',
        'is_published' => 0,
    ]);
});

it('can delete a product and its plans', function () {
    $product = Product::factory()->create();
    Plan::factory()->count(2)->for($product)->create();

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing(Product::class, ['id' => $product->id]);
    assertDatabaseCount(Plan::class, 0);
});

it('requires a renewal interval for subscription plans', function () {
    $product = Product::factory()->create();

    mountPlanCreateAction($product, PriceCategory::Subscription->value)
        ->fillForm([
            'name' => 'Pro',
            'license_activation_limit' => 1,
            'price' => [
                'category' => PriceCategory::Subscription->value,
                'scheme' => PricingScheme::Standard->value,
                'unit_price' => 9.5,
                'renewal_interval_unit' => null,
            ],
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['price.renewal_interval_unit' => ['required']]);

    assertDatabaseCount(Plan::class, 0);
});

function mountPlanCreateAction(Product $product, ?string $category = null): Testable
{
    $component = Livewire::test(PlansRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table());

    if ($category !== null) {
        $component->set('mountedActions.0.data.price.category', $category);
    }

    return $component;
}
