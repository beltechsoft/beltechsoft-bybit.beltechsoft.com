<?php

namespace Modules\ByBitData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ByBitData\Database\Factories\TickerFactory;

class Ticker extends Model
{
    use HasFactory;

    public $table = 'bybit_tickers';

    protected $guarded = ['id'];

}
