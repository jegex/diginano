<?php

use App\Livewire\DownloadCenter;
use App\Models\Download;
use App\Models\License;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductRelease;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeDownloadableLicense(User $user, Plan $plan): License
{
    return License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $plan->product_id,
        'is_active' => true,
    ]);
}

it('lists products the customer holds an active license on, with the latest release', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Awesome Script']);
    $plan = Plan::factory()->for($product)->create();
    makeDownloadableLicense($user, $plan);
    ProductRelease::factory()->for($product)->create(['version' => '1.0', 'changelog' => 'Rilis pertama']);
    ProductRelease::factory()->for($product)->create(['version' => '1.1', 'changelog' => 'Perbaikan bug']);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertSee('Awesome Script')
        ->assertSee('Versi terbaru: 1.1')
        ->assertSee('Perbaikan bug')
        ->assertDontSee('Rilis pertama');
});

it('shows products without a release but no download button', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $plan = Plan::factory()->for($product)->create();
    makeDownloadableLicense($user, $plan);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertSee($product->name)
        ->assertSee('Belum ada rilis untuk produk ini.')
        ->assertDontSeeHtml('wire:click="download(');
});

it('does not list products the customer has no license for', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Hidden Product']);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertDontSee('Hidden Product')
        ->assertSee('Belum ada produk yang bisa Anda unduh.');
});

it('does not list products with an inactive license', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Inactive Product']);
    $plan = Plan::factory()->for($product)->create();
    License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $product->id,
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertDontSee('Inactive Product');
});

it('does not list products of a cancelled subscription license', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Expired Product']);
    $plan = Plan::factory()->subscription()->for($product)->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->cancelled()->create();
    License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $product->id,
        'subscription_id' => $subscription->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertDontSee('Expired Product');
});

it('lists products of an active subscription license', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Active Subscription']);
    $plan = Plan::factory()->subscription()->for($product)->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $product->id,
        'subscription_id' => $subscription->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->assertSee('Active Subscription');
});

it('downloads the latest release and records the download for the license', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $product = Product::factory()->create();
    $plan = Plan::factory()->for($product)->create();
    $license = makeDownloadableLicense($user, $plan);
    $release = ProductRelease::factory()->for($product)->create([
        'file_path' => 'releases/awesome.zip',
        'original_name' => 'awesome.zip',
    ]);
    Storage::disk('local')->put('releases/awesome.zip', 'zip-bytes');

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->call('download', $release->id)
        ->assertFileDownloaded('awesome.zip');

    expect(Download::query()->where('license_id', $license->id)->count())->toBe(1);
    $download = Download::query()->where('license_id', $license->id)->firstOrFail();
    expect($download->product_id)->toBe($product->id)
        ->and($download->release_id)->toBe($release->id);
});

it('refuses to download when the release has no file', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $plan = Plan::factory()->for($product)->create();
    makeDownloadableLicense($user, $plan);
    $release = ProductRelease::factory()->for($product)->noFile()->create();

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->call('download', $release->id)
        ->assertStatus(422);
});

it('refuses to download without a usable license', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $product = Product::factory()->create();
    $release = ProductRelease::factory()->for($product)->create([
        'file_path' => 'releases/awesome.zip',
    ]);
    Storage::disk('local')->put('releases/awesome.zip', 'zip-bytes');

    Livewire::actingAs($user)
        ->test(DownloadCenter::class)
        ->call('download', $release->id)
        ->assertForbidden();

    expect(Download::query()->count())->toBe(0);
});
