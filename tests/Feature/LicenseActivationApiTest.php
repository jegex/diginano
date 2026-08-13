<?php

use App\Models\Activation;
use App\Models\License;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\SubscriptionStatus;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('activates a license key against a domain and enforces the limit', function () {
    $license = License::factory()->create(['activation_limit' => 1]);

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('activated', true)
        ->assertJsonPath('activation_count', 1);

    assertDatabaseHas(Activation::class, [
        'license_id' => $license->id,
        'domain' => 'shop.example.com',
    ]);

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'other.example.com',
    ])->assertStatus(422)
        ->assertJsonPath('valid', false);
});

it('re-activating the same domain is a no-op', function () {
    $license = License::factory()->create(['activation_limit' => 1]);

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk();

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('activated', false)
        ->assertJsonPath('activation_count', 1);

    assertDatabaseCount(Activation::class, 1);
});

it('allows more than one domain when the limit permits it', function () {
    $license = License::factory()->create(['activation_limit' => 3]);

    $this->postJson('/api/licenses/activate', ['key' => $license->key, 'domain' => 'a.example.com'])
        ->assertJsonPath('activation_count', 1);
    $this->postJson('/api/licenses/activate', ['key' => $license->key, 'domain' => 'b.example.com'])
        ->assertJsonPath('activation_count', 2);
    $this->postJson('/api/licenses/activate', ['key' => $license->key, 'domain' => 'c.example.com'])
        ->assertJsonPath('activation_count', 3);
    $this->postJson('/api/licenses/activate', ['key' => $license->key, 'domain' => 'd.example.com'])
        ->assertStatus(422);

    assertDatabaseCount(Activation::class, 3);
});

it('rejects activation when the license is not active', function () {
    $license = License::factory()->inactive()->create();

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertStatus(422)
        ->assertJsonPath('valid', false);

    assertDatabaseCount(Activation::class, 0);
});

it('validates a one-time license as active indefinitely', function () {
    $license = License::factory()->create(['activation_limit' => 1]);
    $license->activateFor('shop.example.com');

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('license.status', 'active')
        ->assertJsonPath('license.valid_until', null);
});

it('rejects validation for a domain that is not activated', function () {
    $license = License::factory()->create(['activation_limit' => 1]);
    $license->activateFor('shop.example.com');

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'unrelated.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', false);
});

it('rejects validation when the license is inactive', function () {
    $license = License::factory()->inactive()->create();

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', false);
});

it('validates a subscription license while its subscription is active', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $license = License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $plan->product_id,
        'subscription_id' => $subscription->id,
        'is_active' => true,
    ]);
    $license->activateFor('shop.example.com');

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('license.valid_until', $subscription->ends_at->toIso8601String());
});

it('invalidates a subscription license after the subscription is cancelled', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create([
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now(),
    ]);
    $license = License::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'product_id' => $plan->product_id,
        'subscription_id' => $subscription->id,
        'is_active' => true,
    ]);
    $license->activateFor('shop.example.com');

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', false);
});

it('revokes an activation and frees the slot', function () {
    $license = License::factory()->create(['activation_limit' => 1]);
    $license->activateFor('shop.example.com');

    $this->postJson('/api/licenses/revoke', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertOk()
        ->assertJsonPath('valid', true);

    assertDatabaseCount(Activation::class, 0);

    $this->postJson('/api/licenses/validate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertJsonPath('valid', false);

    $this->postJson('/api/licenses/activate', [
        'key' => $license->key,
        'domain' => 'shop.example.com',
    ])->assertJsonPath('activated', true);
});

it('returns 404 for an unknown license key', function () {
    $this->postJson('/api/licenses/validate', [
        'key' => 'NOPE-NOPE-NOPE-NOPE',
        'domain' => 'shop.example.com',
    ])->assertNotFound();
});

it('requires a key and domain', function () {
    $this->postJson('/api/licenses/activate', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['key', 'domain']);
});
