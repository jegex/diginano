<?php

use App\DisplayCurrency;
use App\Models\Cart;
use App\Models\ExchangeRate;
use App\Models\License;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\LicenseKeysNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\PaymentReceivedNotification;
use App\OrderStatus;
use App\Services\OrderFinalizer;
use App\SubscriptionStatus;
use Illuminate\Support\Facades\Notification;

function makeOrder(User $user, array $plans = []): Order
{
    auth()->login($user);
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->create();
    ExchangeRate::firstOrCreate(['currency' => DisplayCurrency::Idr], ['rate' => 15000]);

    foreach ($plans as [$plan, $qty]) {
        $cart->add($plan, $qty);
    }

    return Order::checkout($cart, $method);
}

it('completes an order and issues licenses equal to quantity times licenses_per_unit', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create(['price' => 100, 'licenses_per_unit' => 2]);
    $order = makeOrder($user, [[$plan, 3]]);

    app(OrderFinalizer::class)->finalize($order);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed)
        ->and($order->completed_at)->not->toBeNull()
        ->and($order->licenses)->toHaveCount(6)
        ->and($order->licenses->pluck('user_id')->unique())->each->toBe($user->id)
        ->and($order->licenses->pluck('key')->unique())->toHaveCount(6);
});

it('creates a subscription for each subscription-plan item', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $order = makeOrder($user, [[$plan, 1]]);

    app(OrderFinalizer::class)->finalize($order);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed);
    expect(Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->count())->toBe(1);

    $subscription = Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->ends_at->gt(now()))->toBeTrue();

    expect($order->licenses->first()->subscription_id)->toBe($subscription->id);
});

it('extends an existing subscription on renewal', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $first = makeOrder($user, [[$plan, 1]]);
    app(OrderFinalizer::class)->finalize($first);

    $originalEndsAt = $first->refresh()->licenses->first()->subscription->ends_at;
    $renewal = makeOrder($user, [[$plan, 1]]);

    app(OrderFinalizer::class)->finalize($renewal);

    expect(Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->count())->toBe(1);
    $subscription = Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();
    expect($subscription->ends_at->gt($originalEndsAt))->toBeTrue();
});

it('does not create a subscription for one-time plans', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 2]]);

    app(OrderFinalizer::class)->finalize($order);

    expect(Subscription::query()->count())->toBe(0);
});

it('queues the transactional emails on completion', function () {
    Notification::fake();

    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 1]]);

    app(OrderFinalizer::class)->finalize($order);

    Notification::assertSentTo(
        $user,
        PaymentReceivedNotification::class,
    );
    Notification::assertSentTo(
        $user,
        OrderConfirmationNotification::class,
    );
    Notification::assertSentTo(
        $user,
        LicenseKeysNotification::class,
    );
});

it('is idempotent — a second call is a no-op', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 1]]);

    $finalizer = app(OrderFinalizer::class);
    $finalizer->finalize($order);
    $finalizer->finalize($order);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Completed);
    expect($order->licenses)->toHaveCount(1);
});

it('rejects already-completed orders', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 1]]);

    app(OrderFinalizer::class)->finalize($order);

    expect(fn () => app(OrderFinalizer::class)->finalize($order->refresh()))->not->toThrow(Throwable::class);
});

it('rejects expired orders', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 1]]);
    $order->update(['status' => OrderStatus::Expired]);

    expect(fn () => app(OrderFinalizer::class)->finalize($order->refresh()))
        ->toThrow(DomainException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Expired);
    expect(License::query()->count())->toBe(0);
});

it('rejects cancelled orders', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();
    $order = makeOrder($user, [[$plan, 1]]);
    $order->update(['status' => OrderStatus::Cancelled]);

    expect(fn () => app(OrderFinalizer::class)->finalize($order->refresh()))
        ->toThrow(DomainException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect(License::query()->count())->toBe(0);
});
