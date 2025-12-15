WITH interval_vol AS (
    SELECT
        date_trunc('minute', to_timestamp(timestamp_ms / 1000))
            - (extract(minute from to_timestamp(timestamp_ms / 1000))::int % 15) * interval '1 minute'
                                                                 AS interval_start,

        SUM(CASE WHEN direction::int = 1 THEN 1 ELSE 0 END) AS buy_ticks,
        SUM(CASE WHEN direction::int = 0 THEN 1 ELSE 0 END) AS sell_ticks,

        SUM(CASE WHEN direction::int = 1 THEN volume ELSE 0 END) AS buy_volume,
        SUM(CASE WHEN direction::int = 0 THEN volume ELSE 0 END) AS sell_volume,

        SUM(volume) AS total_volume,
        COUNT(*) AS tick_count
    FROM trades
    WHERE symbol = 'ETHUSDT'
    GROUP BY interval_start
)
SELECT *
FROM interval_vol
ORDER BY interval_start DESC;
