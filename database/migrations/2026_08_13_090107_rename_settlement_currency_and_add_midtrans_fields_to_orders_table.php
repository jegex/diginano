<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('bank_currency', 'settlement_currency');
            $table->renameColumn('bank_exchange_rate', 'settlement_exchange_rate');

            $table->string('snap_token')->nullable()->after('settlement_exchange_rate');
            $table->string('snap_redirect_url')->nullable()->after('snap_token');
            $table->string('provider_reference')->nullable()->after('snap_redirect_url');
            $table->string('provider_status')->nullable()->after('provider_reference');
            $table->string('payment_type')->nullable()->after('provider_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('settlement_currency', 'bank_currency');
            $table->renameColumn('settlement_exchange_rate', 'bank_exchange_rate');

            $table->dropColumn(['snap_token', 'snap_redirect_url', 'provider_reference', 'provider_status', 'payment_type']);
        });
    }
};
