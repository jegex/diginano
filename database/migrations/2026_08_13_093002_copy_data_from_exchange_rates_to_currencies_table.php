<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy matching rates from the legacy exchange_rates table into
     * currencies so admin-tuned rates are not lost. The seeder still fills
     * the default set (USD/IDR/EUR) as a safety net.
     */
    public function up(): void
    {
        if (! Schema::hasTable('exchange_rates')) {
            return;
        }

        $rows = DB::table('exchange_rates')->get();

        foreach ($rows as $row) {
            $defaults = match (strtolower((string) $row->currency)) {
                'usd' => ['name' => 'USD (US Dollar)', 'symbol' => '$', 'decimal_places' => 2],
                'idr' => ['name' => 'IDR (Rupiah)', 'symbol' => 'Rp', 'decimal_places' => 0],
                'eur' => ['name' => 'EUR (Euro)', 'symbol' => '€', 'decimal_places' => 2],
                default => [null, null, 2],
            };

            DB::table('currencies')->updateOrInsert(
                ['code' => strtolower((string) $row->currency)],
                [
                    'name' => $defaults[0] ?? strtoupper((string) $row->currency),
                    'symbol' => $defaults[1] ?? strtoupper((string) $row->currency),
                    'exchange_rate' => $row->rate,
                    'decimal_places' => $defaults[2],
                    'is_enabled' => true,
                    'is_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Reverse the migrations. Data is not restored to exchange_rates.
     */
    public function down(): void {}
};
