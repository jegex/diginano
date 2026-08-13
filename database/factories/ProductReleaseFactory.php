<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRelease>
 */
class ProductReleaseFactory extends Factory
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
            'version' => fake()->numerify('1.#.#'),
            'changelog' => fake()->sentence(),
            'file_path' => 'releases/'.fake()->word().'.zip',
            'original_name' => 'release.zip',
        ];
    }

    public function noFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => null,
            'original_name' => null,
        ]);
    }
}
