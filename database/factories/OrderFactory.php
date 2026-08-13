<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'ORD-'.strtoupper(fake()->unique()->bothify('??????')),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'subtotal_usd' => fake()->randomFloat(2, 10, 500),
            'discount_usd' => 0,
            'total_usd' => 0,
            'currency' => 'usd',
            'exchange_rate' => 1,
        ];
    }

    /**
     * Mark the order as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
