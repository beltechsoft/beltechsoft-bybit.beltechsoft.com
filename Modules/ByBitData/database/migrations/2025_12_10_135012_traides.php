<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bybit_trades', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('timestamp_ms')->index();
            $table->smallInteger('symbol')->index();
            // Направление сделки: 1 = Buy, 0 = Sell
            $table->tinyInteger('direction');
            $table->double('price', 16, 8);
            $table->double('volume', 16, 8);
            $table->double('volume_usd', 20, 8)->nullable();

            // Направление тика: 1 = PlusTick, 2 = MinusTick, 3 = ZeroPlusTick
            $table->tinyInteger('tick_direction')->nullable();
            $table->uuid('trade_id')->unique();
            $table->boolean('buy_taker')->default(false);
            $table->boolean('rpi')->default(false);
            $table->bigInteger('seq')->nullable();
            $table->index(['symbol', 'timestamp_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bybit_trades');
    }
};
