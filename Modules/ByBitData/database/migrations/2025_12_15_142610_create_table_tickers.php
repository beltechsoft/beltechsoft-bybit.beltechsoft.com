<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('bybit_tickers', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique(); // Торговая пара
            $table->decimal('last_price', 18, 8)->nullable();
            $table->timestamps();

            $table->index('symbol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('bybit_tickers');
    }
};
