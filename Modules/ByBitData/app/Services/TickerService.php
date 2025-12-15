<?php namespace Modules\ByBitData\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\ByBitData\Models\Ticker;

class TickerService
{
    public function all(): Collection
    {
        return Ticker::query()->get();
    }
}
