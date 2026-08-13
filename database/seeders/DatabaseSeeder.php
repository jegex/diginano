<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
        ]);

        User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
        ]);

        PaymentMethod::firstOrCreate(
            ['type' => PaymentMethodType::Manual, 'name' => 'Bank Transfer (BCA)'],
            [
                'is_enabled' => true,
                'config' => [
                    'bank_name' => 'BCA',
                    'account_name' => 'Diginano Store',
                    'account_number' => '1234567890',
                ],
            ],
        );

        PaymentMethod::firstOrCreate(
            ['type' => PaymentMethodType::Midtrans, 'name' => 'Midtrans'],
            [
                'is_enabled' => true,
                'config' => [
                    'server_key' => 'SB-Mid-server-key',
                    'client_key' => 'SB-Mid-client-key',
                    'is_sandbox' => true,
                ],
            ],
        );
    }
}
