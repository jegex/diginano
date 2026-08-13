<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PaymentMethodType::Manual,
            'name' => 'Bank Transfer',
            'is_enabled' => true,
            'config' => [
                'bank_name' => 'BCA',
                'account_name' => 'Diginano Store',
                'account_number' => '1234567890',
            ],
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Manual,
        ]);
    }

    public function midtrans(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Midtrans,
            'name' => 'Midtrans',
            'config' => [
                'server_key' => 'SB-Mid-server-key',
                'client_key' => 'SB-Mid-client-key',
                'is_sandbox' => true,
            ],
        ]);
    }

    public function cryptomus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethodType::Cryptomus,
            'name' => 'Cryptomus',
            'config' => [
                'merchant_uuid' => 'merchant-uuid',
                'payment_api_key' => 'api-key',
                'is_test' => true,
            ],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
