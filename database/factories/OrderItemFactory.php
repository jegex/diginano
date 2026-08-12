<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = Plan::factory()->create();

        return [
            'order_id' => Order::factory(),
            'plan_id' => $plan->id,
            'product_id' => $plan->product_id,
            'name' => $plan->name,
            'quantity' => 1,
            'unit_price_usd' => $plan->price,
            'line_total_usd' => $plan->price,
            'licenses_per_unit' => $plan->licenses_per_unit,
        ];
    }

    /**
     * Use a specific plan as the snapshot source.
     */
    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
            'product_id' => $plan->product_id,
            'name' => $plan->name,
            'unit_price_usd' => $plan->effectivePriceUsd(),
            'line_total_usd' => $plan->effectivePriceUsd(),
            'licenses_per_unit' => $plan->licenses_per_unit,
        ]);
    }
}
