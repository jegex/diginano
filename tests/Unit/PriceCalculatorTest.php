<?php

use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\UsageAggregation;
use App\Models\Plan;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PriceCalculator;

it('charges standard schemes at unit price times quantity', function () {
    $price = Price::factory()->make(['unit_price' => 9.5]);

    expect(resolve(PriceCalculator::class)->calculate($price, 3))
        ->toMatchArray(['unit_price' => 9.5, 'line_total' => 28.5, 'setup_fee' => 0.0]);
});

it('charges package schemes per package of units', function () {
    $price = Price::factory()->make([
        'scheme' => PricingScheme::Package,
        'unit_price' => 15.0,
        'package_size' => 4,
    ]);

    $calc = resolve(PriceCalculator::class);

    expect($calc->calculate($price, 4)['line_total'])->toBe(15.0)
        ->and($calc->calculate($price, 5)['line_total'])->toBe(30.0)
        ->and($calc->calculate($price, 1)['line_total'])->toBe(15.0);
});

it('charges volume schemes at the rate of the reached tier', function () {
    $price = Price::factory()->make([
        'scheme' => PricingScheme::Volume,
        'tiers' => [
            ['last_unit' => 10, 'unit_price' => 1000, 'fixed_fee' => null],
            ['last_unit' => null, 'unit_price' => 800, 'fixed_fee' => null],
        ],
    ]);

    $calc = resolve(PriceCalculator::class);

    expect($calc->calculate($price, 5)['line_total'])->toBe(50.0)
        ->and($calc->calculate($price, 20)['line_total'])->toBe(160.0);
});

it('charges graduated schemes per unit across each tier', function () {
    $price = Price::factory()->make([
        'scheme' => PricingScheme::Graduated,
        'tiers' => [
            ['last_unit' => 10, 'unit_price' => 1000, 'fixed_fee' => 5000],
            ['last_unit' => null, 'unit_price' => 500, 'fixed_fee' => null],
        ],
    ]);

    $calc = resolve(PriceCalculator::class);

    expect($calc->calculate($price, 10)['line_total'])->toBe(150.0)
        ->and($calc->calculate($price, 20)['line_total'])->toBe(200.0);
});

it('uses the customer amount for pay-what-you-want prices', function () {
    $price = Price::factory()->make([
        'category' => PriceCategory::Pwyw,
        'suggested_price' => 20.0,
    ]);

    expect(resolve(PriceCalculator::class)->calculate($price, 1, customerAmount: 12.5))
        ->toMatchArray(['unit_price' => 12.5, 'line_total' => 12.5, 'setup_fee' => 0.0]);
});

it('treats lead magnets as free', function () {
    $price = Price::factory()->make(['category' => PriceCategory::LeadMagnet]);

    expect(resolve(PriceCalculator::class)->calculate($price, 1))
        ->toMatchArray(['unit_price' => 0.0, 'line_total' => 0.0, 'setup_fee' => 0.0]);
});

it('adds the setup fee once per new subscription line', function () {
    $price = Price::factory()->make([
        'category' => PriceCategory::Subscription,
        'unit_price' => 10.0,
        'setup_fee_enabled' => true,
        'setup_fee' => 25.0,
    ]);

    $calc = resolve(PriceCalculator::class);

    expect($calc->calculate($price, 2)['line_total'])->toBe(20.0)
        ->and($calc->calculate($price, 2)['setup_fee'])->toBe(25.0)
        ->and($calc->calculate($price, 2, includeSetupFee: false)['setup_fee'])->toBe(0.0);
});

it('defers metered usage charges at checkout but bills them at renewal', function () {
    $calc = resolve(PriceCalculator::class);

    $price = Price::factory()->make([
        'category' => PriceCategory::Subscription,
        'unit_price' => 0.05,
        'usage_aggregation' => UsageAggregation::Sum,
    ]);

    expect($calc->calculate($price, 1, deferUsageCharges: true)['line_total'])->toBe(0.0);

    $user = User::factory()->create();
    $plan = Plan::factory()->usageBased()->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();
    $subscription->usageRecords()->createMany([
        ['user_id' => $user->id, 'plan_id' => $plan->id, 'quantity' => 100, 'recorded_at' => now()->subDay()],
        ['user_id' => $user->id, 'plan_id' => $plan->id, 'quantity' => 200, 'recorded_at' => now()],
    ]);

    expect($calc->renewalQuantity($subscription))->toBe(300);
});

it('aggregates metered usage according to the mode', function (UsageAggregation $mode, array $quantities, int $expected) {
    $user = User::factory()->create();
    $plan = Plan::factory()->usageBased($mode)->create();
    $subscription = Subscription::factory()->forPlan($plan, $user)->create();

    foreach ($quantities as $i => $quantity) {
        $subscription->usageRecords()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'quantity' => $quantity,
            'recorded_at' => now()->subDays(count($quantities) - $i),
        ]);
    }

    expect(resolve(PriceCalculator::class)->usageQuantity($subscription))->toBe($expected);
})->with([
    'sum' => [UsageAggregation::Sum, [10, 40], 50],
    'last during period' => [UsageAggregation::LastDuringPeriod, [10, 40], 40],
    'max' => [UsageAggregation::Max, [10, 40], 40],
    'last ever' => [UsageAggregation::LastEver, [10, 40], 40],
]);
