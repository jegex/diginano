<?php

namespace Database\Factories;

use App\BillingPeriod;
use App\Models\Plan;
use App\Models\Product;
use App\PlanPricing;
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
            'pricing_mode' => PlanPricing::OneTime,
            'price' => fake()->randomFloat(2, 5, 200),
            'billing_period' => null,
            'licenses_per_unit' => 1,
        ];
    }

    /**
     * Make the plan a subscription plan.
     */
    public function subscription(BillingPeriod $period = BillingPeriod::Monthly): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_mode' => PlanPricing::Subscription,
            'billing_period' => $period,
        ]);
    }
}
