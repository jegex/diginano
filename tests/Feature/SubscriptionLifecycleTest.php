<?php

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Livewire\SubscriptionsPage;
use App\Models\Cart;
use App\Models\License;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalReminderNotification;
use App\Services\OrderFinalizer;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeRenewalOrder(Subscription $subscription, ?PaymentMethod $method = null): Order
{
    auth()->login($subscription->user);
    $method ??= PaymentMethod::factory()->manual()->create();
    seedCurrencies();

    return Order::renewal($subscription, $method);
}

it('lists the customer subscriptions with their current period end', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create(['name' => 'Pro Plan']);
    Subscription::factory()->forPlan($plan, $user)->create();

    Livewire::actingAs($user)
        ->test(SubscriptionsPage::class)
        ->assertSee('Pro Plan')
        ->assertSee('Periode berakhir:')
        ->assertSee('Perpanjang');
});

it('creates a single-item full-price renewal order with no coupon or discount', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(100))->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $method = PaymentMethod::factory()->manual()->create();

    $order = makeRenewalOrder($subscription, $method);

    expect($order->status)->toBe(OrderStatus::AwaitingConfirmation)
        ->and($order->subscription_id)->toBe($subscription->id)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->unit_price)->toBe(100.0)
        ->and($order->items->first()->line_total)->toBe(100.0)
        ->and($order->subtotal)->toBe(100.0)
        ->and($order->discount)->toBe(0.0)
        ->and($order->total)->toBe(100.0)
        ->and($order->coupon_id)->toBeNull();
});

it('uses pending status and the gateway currency for non-manual renewal orders', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(50))->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $method = PaymentMethod::factory()->midtrans()->create();

    $order = makeRenewalOrder($subscription, $method);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->settlement_currency)->toBe('idr')
        ->and($order->isMidtransPayment())->toBeTrue();
});

it('extends the subscription period when the renewal order is completed', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(100))->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $order = makeRenewalOrder($subscription);

    $originalEndsAt = $subscription->ends_at;

    app(OrderFinalizer::class)->finalize($order);

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->ends_at->gt($originalEndsAt))->toBeTrue();
});

it('refuses to create a renewal order for a cancelled subscription', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->cancelled()->create();
    $method = PaymentMethod::factory()->manual()->create();

    auth()->login($user);

    expect(fn () => Order::renewal($subscription, $method))
        ->toThrow(HttpException::class);
});

it('moves an unrenewed subscription into a 3-day grace period at period end', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create([
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:advance');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($subscription->grace_ends_at)->not->toBeNull()
        ->and($subscription->grace_ends_at->gt(now()->addDays(2)))->toBeTrue()
        ->and($subscription->grace_ends_at->lt(now()->addDays(4)))->toBeTrue();
});

it('sends a renewal reminder email when entering grace', function () {
    Notification::fake();

    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create([
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:advance');

    Notification::assertSentTo($user, RenewalReminderNotification::class);
});

it('cancels a subscription and deactivates its licenses after the grace period', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->subscription()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $license = License::factory()->create([
        'subscription_id' => $subscription->id,
        'activation_limit' => 1,
    ]);

    $subscription->update(['status' => SubscriptionStatus::PastDue]);
    $subscription->update(['grace_ends_at' => now()->subMinute()]);

    $this->artisan('subscriptions:advance');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($license->fresh()->is_active)->toBeFalse();
});

it('cancels at the end of the current period and refuses further renewals', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(100))->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create([
        'ends_at' => now()->addMonth(),
    ]);

    $subscription->cancel();

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->isRenewable())->toBeFalse();
});

it('reactivates a cancelled subscription when a new order is completed', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->withPrice(Price::factory()->subscription()->priced(100))->create();
    $original = Subscription::factory()->forPlan($plan, $user)->create();
    $original->cancel();
    $original->completeCancellation();

    auth()->login($user);
    $cart = Cart::for($user);
    $method = PaymentMethod::factory()->manual()->create();
    seedCurrencies();
    $cart->add($plan, 1);
    $order = Order::checkout($cart, $method);

    app(OrderFinalizer::class)->finalize($order);

    $original->refresh();

    expect(Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->count())->toBe(1)
        ->and($original->status)->toBe(SubscriptionStatus::Active)
        ->and($original->cancelled_at)->toBeNull();
});
