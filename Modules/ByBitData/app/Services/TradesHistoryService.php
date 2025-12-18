<?php

namespace Modules\ByBitData\Services;

use Illuminate\Support\Facades\DB;

class TradesHistoryService
{
    public function getVolumes(array $filters = []){

        $tfSeconds = match ($filters['tf']) {
            '15 minutes' => 15 * 60,
            '4 hours'    => 4 * 60 * 60,
            default      => 15 * 60,
        };

        $fromTimestampMs = (time() - $tfSeconds * 45) * 1000;

        $result = DB::select("
                SELECT
                    date_bin(?, to_timestamp(timestamp_ms / 1000), '1970-01-01') AS tf_15m,
                    SUM(CASE WHEN direction = 1 THEN volume_usd ELSE 0 END) AS buy_volume_usd,
                    SUM(CASE WHEN direction = 0 THEN volume_usd ELSE 0 END) AS sell_volume_usd,
                    SUM(volume_usd) AS total_volume_usd,
                    COUNT(CASE WHEN direction = 1 THEN 1 END) AS buy_ticks_count,
                    COUNT(CASE WHEN direction = 0 THEN 1 END) AS sell_ticks_count,
                    COUNT(*) AS total_ticks_count
                FROM bybit_trades
                WHERE symbol = ?
                  AND timestamp_ms >= ?
                GROUP BY tf_15m
                ORDER BY tf_15m DESC
                LIMIT 45", [
            $filters['tf'],
            $filters['symbol'],
            $fromTimestampMs
        ]);

        return $result;

    }
}
