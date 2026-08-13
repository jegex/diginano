<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the money columns from decimal dollars to unsignedBigInteger
     * cents. Data is multiplied by 100 BEFORE the column type is narrowed,
     * so MySQL never truncates the fractional part.
     */
    public function up(): void
    {
        DB::table('plans')->update([
            'price' => DB::raw('price * 100'),
            'sale_price' => DB::raw('sale_price * 100'),
        ]);

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->change();
            $table->unsignedBigInteger('sale_price')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('subtotal_usd', 'subtotal');
            $table->renameColumn('discount_usd', 'discount');
            $table->renameColumn('total_usd', 'total');
        });

        DB::table('orders')->update([
            'subtotal' => DB::raw('subtotal * 100'),
            'discount' => DB::raw('discount * 100'),
            'total' => DB::raw('total * 100'),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->change();
            $table->unsignedBigInteger('discount')->default(0)->change();
            $table->unsignedBigInteger('total')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('unit_price_usd', 'unit_price');
            $table->renameColumn('line_total_usd', 'line_total');
        });

        DB::table('order_items')->update([
            'unit_price' => DB::raw('unit_price * 100'),
            'line_total' => DB::raw('line_total * 100'),
        ]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->change();
            $table->unsignedBigInteger('line_total')->change();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->nullable()->change();
            $table->unsignedBigInteger('fixed_value')->nullable()->after('value');
        });

        DB::table('coupons')
            ->where('type', 'fixed')
            ->update([
                'fixed_value' => DB::raw('value * 100'),
                'value' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('coupons')
            ->where('type', 'fixed')
            ->update([
                'value' => DB::raw('fixed_value / 100'),
            ]);

        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->change();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('fixed_value');
        });

        // Widen the columns back to decimal BEFORE dividing so the cents are
        // not truncated.
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->change();
            $table->decimal('sale_price', 12, 2)->nullable()->change();
        });

        DB::table('plans')->update([
            'price' => DB::raw('price / 100'),
            'sale_price' => DB::raw('sale_price / 100'),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->change();
            $table->decimal('discount', 12, 2)->default(0)->change();
            $table->decimal('total', 12, 2)->change();
        });

        DB::table('orders')->update([
            'subtotal' => DB::raw('subtotal / 100'),
            'discount' => DB::raw('discount / 100'),
            'total' => DB::raw('total / 100'),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('subtotal', 'subtotal_usd');
            $table->renameColumn('discount', 'discount_usd');
            $table->renameColumn('total', 'total_usd');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('line_total', 12, 2)->change();
        });

        DB::table('order_items')->update([
            'unit_price' => DB::raw('unit_price / 100'),
            'line_total' => DB::raw('line_total / 100'),
        ]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('unit_price', 'unit_price_usd');
            $table->renameColumn('line_total', 'line_total_usd');
        });
    }
};
