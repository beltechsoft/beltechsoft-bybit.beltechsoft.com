<?php

namespace Modules\ByBitData\Entity;

use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CandleEntity implements ArrayAccess
{

    public string $symbol;
    public float $open;
    public float $close;
    public float $high;
    public float $low;
    public float $volume;
    public float $volume_usdt;
    public int $start_ts;      // timestamp начала свечи
    public int $end_ts;        // timestamp конца свечи
    public bool $confirm;
    public float $price_change;
    public string $direction = 'flat'; // направление свечи: up/down/flat
    public ?Carbon $start;
    public int $time_ts;
    public Carbon $time;
    public float $current_price;


    static public function webService($candle): self
    {
        $self = new self();
        $self->symbol   = $candle['symbol'] ?? '';
        $self->open     = (float)($candle['open'] ?? 0);
        $self->close    = (float)($candle['close'] ?? 0);
        $self->current_price = $self->close;
        $self->high     = (float)($candle['high'] ?? 0);
        $self->low      = (float)($candle['low'] ?? 0);
        $self->volume   = (float)($candle['volume'] ?? 0);
        $self->volume_usdt = (float)($candle['turnover'] ?? 0);
        $self->start_ts    = $candle['start'] ?? 0;
        $self->end_ts      = $candle['end'] ?? 0;
        $self->time_ts    = $candle['timestamp'];
        $self->time = Carbon::createFromTimestampMs($self->time_ts);
        $self->confirm  = Arr::get($candle,'confirm', true);
        $self->price_change = (float)($self->close - $self->open) / $self->open * 100;
        $self->direction = $self->close > $self->open ? 'up' : ($self->close < $self->open ? 'down' : 'flat');

        return $self;
    }


    // ArrayAccess
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset): void
    {
        $this->$offset = null;
    }
}
