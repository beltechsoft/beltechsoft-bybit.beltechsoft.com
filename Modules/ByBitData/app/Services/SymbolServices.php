<?php namespace Modules\ByBitData\Services;

use Modules\ByBitData\Models\Ticker;

class SymbolServices
{
    public function get(){
        return Ticker::whereIn('symbol', ['ETHUSDT', 'BTCUSDT', 'SOLUSDT', 'ASTERUSDT'])->get();
    }
}
