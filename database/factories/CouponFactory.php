<?php

namespace Database\Factories;

use App\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(10)),
            'type' => CouponType::Percentage,
            'value' => fake()->randomFloat(2, 1, 50),
            'is_single_use' => false,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percentage,
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Fixed,
        ]);
    }

    public function singleUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_single_use' => true,
        ]);
    }
}
