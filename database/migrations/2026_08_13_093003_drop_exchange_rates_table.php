<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('exchange_rates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3)->unique();
            $table->decimal('rate', 18, 6);
            $table->timestamps();
        });
    }
};
