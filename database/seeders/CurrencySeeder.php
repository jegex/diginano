<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed the default currency set. The base currency is USD (rate 1); IDR
     * and EUR are provided as common display currencies. Existing rows are
     * left untouched so admin-tuned rates survive re-seeding.
     */
    public function run(): void
    {
        $defaults = [
            ['code' => 'usd', 'name' => 'USD (US Dollar)', 'symbol' => '$', 'exchange_rate' => 1, 'decimal_places' => 2, 'is_enabled' => true, 'is_default' => true],
            ['code' => 'idr', 'name' => 'IDR (Rupiah)', 'symbol' => 'Rp', 'exchange_rate' => 15000, 'decimal_places' => 0, 'is_enabled' => true, 'is_default' => false],
            ['code' => 'eur', 'name' => 'EUR (Euro)', 'symbol' => '€', 'exchange_rate' => 0.9, 'decimal_places' => 2, 'is_enabled' => true, 'is_default' => false],
        ];

        foreach ($defaults as $data) {
            $currency = Currency::query()->where('code', $data['code'])->first();

            if ($currency === null) {
                Currency::create($data);

                continue;
            }

            $currency->update([
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'decimal_places' => $data['decimal_places'],
                'is_enabled' => $data['is_enabled'],
                'is_default' => $data['is_default'],
            ]);
        }
    }
}
