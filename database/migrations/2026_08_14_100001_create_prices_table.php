<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 1:1 Price row holds all pricing for a Plan variant. Money columns
     * are unsignedBigInteger cents (see App\Casts\MoneyCast).
     */
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('scheme')->default('standard');
            $table->string('usage_aggregation')->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->boolean('setup_fee_enabled')->default(false);
            $table->unsignedBigInteger('setup_fee')->nullable();
            $table->unsignedInteger('package_size')->default(1);
            $table->json('tiers')->nullable();
            $table->string('renewal_interval_unit')->nullable();
            $table->unsignedInteger('renewal_interval_quantity')->nullable();
            $table->string('trial_interval_unit')->nullable();
            $table->unsignedInteger('trial_interval_quantity')->nullable();
            $table->unsignedBigInteger('min_price')->nullable();
            $table->unsignedBigInteger('suggested_price')->nullable();
            $table->timestamps();

            $table->unique('plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
