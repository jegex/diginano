<?php

namespace Database\Factories;

use App\DisplayCurrency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'currency' => DisplayCurrency::Idr,
            'rate' => fake()->randomFloat(2, 1000, 20000),
        ];
    }
}
