<?php

use App\Livewire\Catalog;
use App\Livewire\ProductDetail;
use App\Models\Plan;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(fn () => seedCurrencies());

it('lists published products on the catalog', function () {
    $published = Product::factory()->create(['name' => 'Awesome Script']);
    $unpublished = Product::factory()->create(['is_published' => false, 'name' => 'Hidden Product']);

    Livewire::test(Catalog::class)
        ->assertOk()
        ->assertSee('Awesome Script')
        ->assertDontSee('Hidden Product');
});

it('shows the plan count on the catalog', function () {
    $product = Product::factory()->create();
    Plan::factory()->count(2)->for($product)->create();

    Livewire::test(Catalog::class)
        ->assertSee($product->name)
        ->assertSee('2 plans');
});

it('links each product to its detail page', function () {
    $product = Product::factory()->create();

    Livewire::test(Catalog::class)
        ->assertSeeHtml(route('product-detail', $product));
});

it('shows an empty state when no products exist', function () {
    Livewire::test(Catalog::class)
        ->assertSee('Belum ada produk yang tersedia.');
});

it('shows the product detail', function () {
    $product = Product::factory()->create(['description' => 'Deskripsi lengkap produk']);
    $plan = Plan::factory()->priced(99.5)->for($product)->create(['name' => 'Lifetime']);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertOk()
        ->assertSee($product->name)
        ->assertSee('Deskripsi lengkap produk')
        ->assertSee('Lifetime')
        ->assertSee('99.50');
});

it('renders one-time and subscription pricing differently', function () {
    $product = Product::factory()->create();
    Plan::factory()->for($product)->create(['name' => 'Sekali Bayar']);
    Plan::factory()->subscription()->for($product)->create(['name' => 'Langganan']);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertSee('Bayar sekali — berlaku selamanya')
        ->assertSee('Per bulan');
});

it('returns 404 for an unpublished product', function () {
    $product = Product::factory()->create(['is_published' => false]);

    Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertStatus(404);
});

it('hides a product page once it is unpublished', function () {
    $product = Product::factory()->create();

    $component = Livewire::test(ProductDetail::class, ['product' => $product])
        ->assertOk();

    $product->update(['is_published' => false]);

    $component->call('$refresh')->assertStatus(404);
});
