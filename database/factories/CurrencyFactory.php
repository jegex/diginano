<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'usd',
            'name' => 'USD (US Dollar)',
            'symbol' => '$',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'is_enabled' => true,
            'is_default' => true,
        ];
    }

    public function idr(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'idr',
            'name' => 'IDR (Rupiah)',
            'symbol' => 'Rp',
            'exchange_rate' => 15000,
            'decimal_places' => 0,
            'is_default' => false,
        ]);
    }

    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'eur',
            'name' => 'EUR (Euro)',
            'symbol' => '€',
            'exchange_rate' => 0.9,
            'decimal_places' => 2,
            'is_default' => false,
        ]);
    }
}
