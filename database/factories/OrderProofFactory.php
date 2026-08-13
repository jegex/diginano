<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderProof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderProof>
 */
class OrderProofFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'file_path' => 'proofs/'.fake()->uuid().'.jpg',
            'original_name' => 'bukti-transfer.jpg',
            'submitted_at' => now(),
        ];
    }
}
