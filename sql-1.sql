WITH interval_vol AS (
    SELECT
        date_trunc('minute', to_timestamp(timestamp_ms / 1000))
            - (extract(minute from to_timestamp(timestamp_ms / 1000))::int % 15) * interval '1 minute'
    AS interval_start,

-- Количество тиков Buy/Sell
    SUM(CASE WHEN direction::int = 1 THEN 1 ELSE 0 END) AS buy_ticks,
    SUM(CASE WHEN direction::int = 0 THEN 1 ELSE 0 END) AS sell_ticks,

-- Объёмы
    SUM(CASE WHEN direction::int = 1 THEN volume ELSE 0 END) AS buy_volume,
    SUM(CASE WHEN direction::int = 0 THEN volume ELSE 0 END) AS sell_volume,

-- Общий объём
    SUM(volume) AS total_volume,

-- Общее количество тиков
    COUNT(*) AS tick_count
FROM trades
WHERE symbol = 'ETHUSDT'
GROUP BY interval_start
    ),
    spike_calc AS (
SELECT
    curr.interval_start,



    -- Объёмы
    curr.buy_volume,
    curr.sell_volume,
    curr.total_volume,

    -- Предыдущие данные
    prev.buy_volume  AS prev_buy,
    prev.sell_volume AS prev_sell,
    prev.total_volume AS prev_total,
    -- Тики
    curr.buy_ticks,
    curr.sell_ticks,
    curr.tick_count,

    -- Спайки
    CASE
    WHEN prev.buy_volume > 0 AND curr.buy_volume > 2 * prev.buy_volume THEN TRUE
    ELSE FALSE
    END AS buy_spike,

    CASE
    WHEN prev.sell_volume > 0 AND curr.sell_volume > 2 * prev.sell_volume THEN TRUE
    ELSE FALSE
    END AS sell_spike,

    CASE
    WHEN prev.total_volume > 0 AND curr.total_volume > 2 * prev.total_volume THEN TRUE
    ELSE FALSE
    END AS total_spike
FROM interval_vol curr
    LEFT JOIN interval_vol prev
ON prev.interval_start = curr.interval_start - interval '15 minutes'
    )
SELECT *
FROM spike_calc
ORDER BY interval_start;
