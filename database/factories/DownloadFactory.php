<?php

namespace Database\Factories;

use App\Models\Download;
use App\Models\License;
use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'product_id' => Product::factory(),
            'release_id' => ProductRelease::factory(),
            'downloaded_at' => now(),
        ];
    }
}
