<?php

use App\CouponType;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Models\Coupon;
use App\Models\Product;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

it('can create a global percentage coupon', function () {
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'HELLO10',
            'type' => CouponType::Percentage->value,
            'value' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Coupon::class, [
        'code' => 'HELLO10',
        'type' => 'percentage',
        'value' => '10.00',
    ]);
});

it('can create a product-scoped single-use fixed coupon', function () {
    $product = Product::factory()->create();

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'FIXED5',
            'type' => CouponType::Fixed->value,
            'value' => 5,
            'is_single_use' => true,
            'products' => [$product->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Coupon::class, [
        'code' => 'FIXED5',
        'is_single_use' => true,
    ]);

    $coupon = Coupon::where('code', 'FIXED5')->firstOrFail();

    expect($coupon->products()->pluck('products.id'))->toContain($product->id);
});

it('normalizes the coupon code to uppercase', function () {
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'diskon10',
            'type' => CouponType::Percentage->value,
            'value' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Coupon::class, [
        'code' => 'DISKON10',
    ]);
});

it('requires a coupon code', function () {
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => null,
            'type' => CouponType::Percentage->value,
            'value' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => ['required']]);
});

it('requires a unique coupon code', function () {
    Coupon::factory()->create(['code' => 'HELLO10']);

    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'HELLO10',
            'type' => CouponType::Percentage->value,
            'value' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => ['unique']]);
});

it('requires a valid coupon type', function () {
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'BOGUS',
            'type' => 'bogus',
            'value' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['type']);
});

it('requires a positive coupon value', function () {
    Livewire::test(CreateCoupon::class)
        ->fillForm([
            'code' => 'ZERO',
            'type' => CouponType::Percentage->value,
            'value' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['value' => ['min']]);
});

it('can edit a coupon', function () {
    $coupon = Coupon::factory()->percentage()->create(['code' => 'HELLO10', 'value' => 10]);

    Livewire::test(EditCoupon::class, ['record' => $coupon->getRouteKey()])
        ->fillForm([
            'code' => 'HELLO20',
            'type' => CouponType::Percentage->value,
            'value' => 20,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(Coupon::class, [
        'id' => $coupon->id,
        'code' => 'HELLO20',
        'value' => '20.00',
    ]);
});
