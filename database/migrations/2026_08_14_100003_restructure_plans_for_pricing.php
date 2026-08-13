<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restructure plans into a variant row + 1:1 price row (Lemon Squeezy
     * style), drop the sale feature, and prepare the license/subscription
     * columns for the new licensing rules.
     */
    public function up(): void
    {
        // 1) Backfill one Price row per existing Plan (money is already cents
        //    after the store_money_as_integer_cents migration). Uses the query
        //    builder so it stays portable across MySQL and SQLite.
        $now = now();

        $priceRows = DB::table('plans')
            ->select('id', 'pricing_mode', 'price', 'billing_period')
            ->get()
            ->map(function ($plan) use ($now) {
                $interval = match ($plan->billing_period) {
                    'monthly', 'quarterly' => 'month',
                    'yearly' => 'year',
                    default => null,
                };

                $quantity = match ($plan->billing_period) {
                    'monthly' => 1,
                    'quarterly' => 3,
                    'yearly' => 1,
                    default => null,
                };

                return [
                    'plan_id' => $plan->id,
                    'category' => $plan->pricing_mode === 'subscription' ? 'subscription' : 'one_time',
                    'scheme' => 'standard',
                    'unit_price' => $plan->price,
                    'renewal_interval_unit' => $interval,
                    'renewal_interval_quantity' => $quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        DB::table('prices')->insert($priceRows);

        // 2) Plans become the SKU/variant row.
        Schema::table('plans', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('has_license_keys')->default(true)->after('description');
            $table->renameColumn('activation_limit', 'license_activation_limit');
            $table->boolean('is_license_limit_unlimited')->default(false)->after('license_activation_limit');
            $table->unsignedInteger('license_length_value')->nullable()->after('is_license_limit_unlimited');
            $table->string('license_length_unit')->nullable()->after('license_length_value');
            $table->boolean('is_license_length_unlimited')->default(true)->after('license_length_unit');
            $table->unsignedInteger('sort')->default(0)->after('is_license_length_unlimited');
            $table->string('status')->default('pending')->after('sort');
        });

        DB::table('plans')->update(['status' => 'published']);

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['pricing_mode', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'billing_period', 'licenses_per_unit']);
        });

        // 3) Order items snapshot the one-time setup fee for the first
        //    subscription cycle; licenses_per_unit is gone (1 license/unit).
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('setup_fee')->nullable()->after('line_total');
            $table->dropColumn('licenses_per_unit');
        });

        // 4) Subscriptions snapshot the quantity bought at checkout so a
        //    renewal can be billed with the same quantity.
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('plan_id');
        });

        // 5) A null activation_limit on a License means unlimited activations.
        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedInteger('activation_limit')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->renameColumn('license_activation_limit', 'activation_limit');
            $table->dropColumn(['description', 'has_license_keys', 'is_license_limit_unlimited', 'license_length_value', 'license_length_unit', 'is_license_length_unlimited', 'sort', 'status']);
            $table->string('pricing_mode');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->string('billing_period')->nullable();
            $table->unsignedInteger('licenses_per_unit')->default(1);
        });

        DB::table('prices')->get(['plan_id', 'category', 'unit_price', 'renewal_interval_unit', 'renewal_interval_quantity'])
            ->each(function ($price): void {
                DB::table('plans')->where('id', $price->plan_id)->update([
                    'pricing_mode' => $price->category === 'subscription' ? 'subscription' : 'one-time',
                    'price' => $price->unit_price ?? 0,
                    'billing_period' => match ($price->renewal_interval_quantity) {
                        3 => 'quarterly',
                        default => $price->renewal_interval_unit === 'year' ? 'yearly' : ($price->renewal_interval_unit === 'month' ? 'monthly' : null),
                    },
                ]);
            });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('licenses_per_unit')->default(1)->after('line_total');
            $table->dropColumn('setup_fee');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->unsignedInteger('activation_limit')->default(1)->nullable(false)->change();
        });

        Schema::dropIfExists('prices');
        Schema::dropIfExists('usage_records');
    }
};
