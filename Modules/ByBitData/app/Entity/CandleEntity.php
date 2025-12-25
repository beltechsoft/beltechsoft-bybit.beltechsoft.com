<?php

namespace Modules\ByBitData\Entity;

use ArrayAccess;
use Illuminate\Support\Arr;

class CandleEntity implements ArrayAccess
{

    public string $symbol;
    public float $open;
    public float $close;
    public float $high;
    public float $low;
    public float $volume;
    public float $volume_usdt;
    public int $start;      // timestamp начала свечи
    public int $end;        // timestamp конца свечи
    public bool $confirm;
    public float $priceChange;
    public string $direction = 'flat'; // направление свечи: up/down/flat


    static public function webService($kline)
    {
        $self = new self();
        $self->symbol   = $kline['symbol'] ?? '';
        $self->open     = (float)($kline['open'] ?? 0);
        $self->close    = (float)($kline['close'] ?? 0);
        $self->high     = (float)($kline['high'] ?? 0);
        $self->low      = (float)($kline['low'] ?? 0);
        $self->volume   = (float)($kline['volume'] ?? 0);
        $self->volume_usdt = (float)($kline['turnover'] ?? 0);
        $self->start    = $kline['start'] ?? 0;
        $self->end      = $kline['end'] ?? 0;
        $self->confirm  = Arr::get($kline,'confirm', true);
        $self->priceChange = (float)($self->close - $self->open) / $self->open * 100;
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
