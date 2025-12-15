WITH leverage AS (
    SELECT unnest(array[2,5,7,10,20,30]) AS lev
),
     liq_calc AS (
         SELECT
             t.timestamp_ms,
             t.symbol,
             t.direction::int AS direction,
             t.volume,
             t.price AS entry_price,
             t.volume * t.price AS position_value,
             t.volume * t.price / l.lev AS margin,
             l.lev,
             CASE
                 WHEN t.direction::int = 1 THEN t.price * (1 - (t.volume*t.price)/(t.volume*t.price/l.lev))
    ELSE t.price * (1 + (t.volume*t.price)/(t.volume*t.price/l.lev))
END AS liq_price
    FROM trades t
    CROSS JOIN leverage l
    WHERE t.symbol = 'ETHUSDT'
)
SELECT *
FROM liq_calc
ORDER BY timestamp_ms DESC, lev;
