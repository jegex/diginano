<?php

namespace Database\Factories;

use App\Enums\PlanStatus;
use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\RenewalIntervalUnit;
use App\Enums\UsageAggregation;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->word(),
            'has_license_keys' => true,
            'license_activation_limit' => 1,
            'is_license_limit_unlimited' => false,
            'is_license_length_unlimited' => true,
            'sort' => 0,
            'status' => PlanStatus::Published,
        ];
    }

    /**
     * Give every factory-created plan a default 1:1 Price unless one already
     * exists. Pricing states build their price via `withPrice()`, which is
     * created before this callback runs.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Plan $plan): void {
            if ($plan->price()->exists()) {
                return;
            }

            $plan->price()->create([
                'category' => PriceCategory::OneTime,
                'scheme' => PricingScheme::Standard,
                'unit_price' => fake()->randomFloat(2, 5, 200),
            ]);
        });
    }

    /**
     * Attach a price built with the PriceFactory. Chain states on the price
     * factory for composed pricing (e.g. subscription with a setup fee).
     */
    public function withPrice(PriceFactory $factory): static
    {
        return $this->has($factory, 'price');
    }

    public function priced(float $amount): static
    {
        return $this->withPrice(PriceFactory::new()->priced($amount));
    }

    public function subscription(
        RenewalIntervalUnit $unit = RenewalIntervalUnit::Month,
        int $quantity = 1,
    ): static {
        return $this->withPrice(PriceFactory::new()->subscription($unit, $quantity));
    }

    public function leadMagnet(): static
    {
        return $this->withPrice(PriceFactory::new()->leadMagnet());
    }

    public function pwyw(float $suggested, float $min = 0): static
    {
        return $this->withPrice(PriceFactory::new()->pwyw($suggested, $min));
    }

    public function usageBased(UsageAggregation $mode = UsageAggregation::Sum): static
    {
        return $this->withPrice(PriceFactory::new()->usageBased($mode));
    }

    public function setupFee(float $amount): static
    {
        return $this->withPrice(PriceFactory::new()->setupFee($amount));
    }

    /**
     * @param  array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>  $tiers
     */
    public function volume(array $tiers): static
    {
        return $this->withPrice(PriceFactory::new()->volume($tiers));
    }

    /**
     * @param  array<int, array{last_unit: int|null, unit_price: int, fixed_fee: int|null}>  $tiers
     */
    public function graduated(array $tiers): static
    {
        return $this->withPrice(PriceFactory::new()->graduated($tiers));
    }

    public function package(float $unit, int $size): static
    {
        return $this->withPrice(PriceFactory::new()->package($unit, $size));
    }
}
