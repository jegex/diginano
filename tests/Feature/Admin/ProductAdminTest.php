<?php

use App\Enums\BillingPeriod;
use App\Enums\PlanPricing;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\PlansRelationManager;
use App\Models\Plan;
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
    $plan = Plan::factory()->make([
        'pricing_mode' => PlanPricing::OneTime,
        'billing_period' => null,
        'licenses_per_unit' => 3,
    ]);

    mountPlanCreateAction($product)
        ->fillForm([
            'name' => $plan->name,
            'pricing_mode' => $plan->pricing_mode->value,
            'price' => $plan->price,
            'licenses_per_unit' => $plan->licenses_per_unit,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Plan::class, [
        'product_id' => $product->id,
        'name' => $plan->name,
        'pricing_mode' => PlanPricing::OneTime->value,
        'billing_period' => null,
        'price' => $plan->price,
        'licenses_per_unit' => 3,
    ]);
});

it('can create a subscription plan through the relation manager', function () {
    $product = Product::factory()->create();
    $plan = Plan::factory()->subscription(BillingPeriod::Monthly)->make();

    mountPlanCreateAction($product, PlanPricing::Subscription->value)
        ->fillForm([
            'name' => $plan->name,
            'price' => $plan->price,
            'billing_period' => $plan->billing_period->value,
            'licenses_per_unit' => $plan->licenses_per_unit,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Plan::class, [
        'product_id' => $product->id,
        'pricing_mode' => PlanPricing::Subscription->value,
        'billing_period' => BillingPeriod::Monthly->value,
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

it('requires a billing period for subscription plans', function () {
    $product = Product::factory()->create();
    $plan = Plan::factory()->subscription()->make();

    mountPlanCreateAction($product, PlanPricing::Subscription->value)
        ->fillForm([
            'name' => $plan->name,
            'price' => $plan->price,
            'licenses_per_unit' => $plan->licenses_per_unit,
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['billing_period' => ['required']]);

    assertDatabaseCount(Plan::class, 0);
});

function mountPlanCreateAction(Product $product, ?string $pricingMode = null): Testable
{
    $component = Livewire::test(PlansRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table());

    if ($pricingMode !== null) {
        $component->set('mountedActions.0.data.pricing_mode', $pricingMode);
    }

    return $component;
}
