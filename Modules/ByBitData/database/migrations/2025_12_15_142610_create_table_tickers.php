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
        Schema::create('bybit_tickers', function (Blueprint $table) {

            $table->id();
            $table->string('symbol')->unique(); // Торговая пара
            $table->decimal('last_price', 30, 8)->nullable();
            $table->decimal('index_price', 30, 8)->nullable();
            $table->decimal('mark_price', 30, 8)->nullable();
            $table->decimal('prev_price_24h', 30, 8)->nullable();
            $table->decimal('price_24h_pcnt', 30, 8)->nullable();
            $table->decimal('high_price_24h', 30, 8)->nullable();
            $table->decimal('low_price_24h', 30, 8)->nullable();
            $table->decimal('prev_price_1h', 30, 8)->nullable();
            $table->decimal('open_interest', 30, 8)->nullable();
            $table->decimal('open_interest_value', 30, 8)->nullable();
            $table->decimal('turnover_24h', 24, 8)->nullable();
            $table->decimal('volume_24h', 24, 8)->nullable();
            $table->decimal('funding_rate', 30, 8)->nullable();
            $table->bigInteger('next_funding_time')->nullable();
            $table->decimal('ask1_size', 30, 8)->nullable();
            $table->decimal('bid1_price', 30, 8)->nullable();
            $table->decimal('ask1_price', 30, 8)->nullable();
            $table->decimal('bid1_size', 30, 8)->nullable();
            $table->string('cur_pre_listing_phase')->nullable();
            $table->integer('funding_interval_hour')->nullable();
            $table->decimal('funding_cap', 30, 8)->nullable();

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
