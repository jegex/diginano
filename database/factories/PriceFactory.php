<?php

namespace Database\Factories;

use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\RenewalIntervalUnit;
use App\Enums\UsageAggregation;
use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    /**
     * plan_id is intentionally absent from the definition: a Price is always
     * created through the PlanFactory `has(Price::factory(), 'price')`
     * relationship (which injects plan_id), or with an explicit plan_id.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => PriceCategory::OneTime,
            'scheme' => PricingScheme::Standard,
            'unit_price' => fake()->randomFloat(2, 5, 200),
        ];
    }

    public function priced(float $amount): static
    {
        return $this->state(['unit_price' => $amount]);
    }

    public function subscription(
        RenewalIntervalUnit $unit = RenewalIntervalUnit::Month,
        int $quantity = 1,
    ): static {
        return $this->state([
            'category' => PriceCategory::Subscription,
            'unit_price' => fake()->randomFloat(2, 5, 200),
            'renewal_interval_unit' => $unit,
            'renewal_interval_quantity' => $quantity,
        ]);
    }

    public function leadMagnet(): static
    {
        return $this->state([
            'category' => PriceCategory::LeadMagnet,
            'unit_price' => null,
        ]);
    }

    public function pwyw(float $suggested, float $min = 0): static
    {
        return $this->state([
            'category' => PriceCategory::Pwyw,
            'unit_price' => null,
            'suggested_price' => $suggested,
            'min_price' => $min,
        ]);
    }

    public function usageBased(UsageAggregation $mode = UsageAggregation::Sum): static
    {
        return $this->state([
            'category' => PriceCategory::Subscription,
            'unit_price' => fake()->randomFloat(2, 0.01, 5),
            'usage_aggregation' => $mode,
            'renewal_interval_unit' => RenewalIntervalUnit::Month,
            'renewal_interval_quantity' => 1,
        ]);
    }

    public function setupFee(float $amount): static
    {
        return $this->state([
            'setup_fee_enabled' => true,
            'setup_fee' => $amount,
        ]);
    }

    /**
     * @param  array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>  $tiers
     */
    public function volume(array $tiers): static
    {
        return $this->state([
            'scheme' => PricingScheme::Volume,
            'tiers' => $tiers,
        ]);
    }

    /**
     * @param  array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>  $tiers
     */
    public function graduated(array $tiers): static
    {
        return $this->state([
            'scheme' => PricingScheme::Graduated,
            'tiers' => $tiers,
        ]);
    }

    public function package(float $unit, int $size): static
    {
        return $this->state([
            'scheme' => PricingScheme::Package,
            'unit_price' => $unit,
            'package_size' => $size,
        ]);
    }
}
