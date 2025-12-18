<?php

namespace Modules\ByBitData\Services;

use Illuminate\Support\Facades\DB;

class TradesHistoryService
{
    public function getVolumes(array $filters = [])
    {
        // таймфрейм в миллисекундах
        $tfMs = match ($filters['tf']) {
            '15 minutes' => 15 * 60 * 1000,
            '4 hours'    => 4 * 60 * 60 * 1000,
            default      => 15 * 60 * 1000,
        };

        // последние 45 свечей
        $fromTimestampMs = (time() * 1000) - ($tfMs * 45);

        $result = DB::select("
        SELECT
            (timestamp_ms / ?) * ? AS tf_ms,

            SUM(volume_usd) FILTER (WHERE direction = 1) AS buy_volume_usd,
            SUM(volume_usd) FILTER (WHERE direction = 0) AS sell_volume_usd,
            SUM(volume_usd) AS total_volume_usd,

            COUNT(*) FILTER (WHERE direction = 1) AS buy_ticks_count,
            COUNT(*) FILTER (WHERE direction = 0) AS sell_ticks_count,
            COUNT(*) AS total_ticks_count

        FROM bybit_trades
        WHERE symbol = ?
          AND timestamp_ms >= ?

        GROUP BY tf_ms
        ORDER BY tf_ms DESC
        LIMIT 45
    ", [
            $tfMs,                // для деления
            $tfMs,                // для умножения
            $filters['symbol'],
            $fromTimestampMs,
        ]);


        return $result;
    }
}
