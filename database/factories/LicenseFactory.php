<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = Plan::factory()->create();
        $user = User::factory()->create();

        return [
            'key' => License::generateKey(),
            'user_id' => $user->id,
            'order_id' => Order::factory(),
            'order_item_id' => OrderItem::factory(),
            'plan_id' => $plan->id,
            'product_id' => $plan->product_id,
            'is_active' => true,
            'activation_limit' => $plan->activationLimit(),
        ];
    }

    public function forPlan(Plan $plan, User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_id' => $plan->id,
            'product_id' => $plan->product_id,
            'user_id' => $user->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
