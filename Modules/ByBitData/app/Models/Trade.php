<?php

namespace Modules\ByBitData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trade extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $table = 'bybit_trades';

    protected $guarded = ['id'];
}
