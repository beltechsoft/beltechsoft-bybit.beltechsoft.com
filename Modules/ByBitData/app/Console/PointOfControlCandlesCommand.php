<?php namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PointOfControlCandlesCommand extends Command
{
    protected $signature = 'bybit:point-of-control-candles
                            {symbol=BTCUSDT}
                            {interval=240}
                            {limit=90}
                            {category=linear}
                            {bins=15}';

    protected $description = 'Fetch Bybit V5 candles, calculate POC, Value Area, VWAP and top 7 volume bins with ASCII chart';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $interval = $this->argument('interval');
        $limit = $this->argument('limit');
        $category = $this->argument('category');
        $binsCount = (int)$this->argument('bins');

        $this->info("Volume Profile with POC, Value Area and VWAP (Top 7 Bins)");
        $this->info("Symbol: {$symbol} | Interval: {$interval} | Bins Count: {$binsCount}");
        $this->line(str_repeat("=", 70));

        // 1️⃣ Получаем свечи
        $candles = Http::get("https://api.bybit.com/v5/market/kline", [
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit,
            'category' => $category,
        ])->json()['result']['list'] ?? [];

        if (empty($candles)) {
            $this->error("No candles returned");
            return 1;
        }

        // 2️⃣ Диапазон профиля
        $minLow = INF;
        $maxHigh = 0;
        foreach ($candles as $c) {
            $minLow = min($minLow, (float)$c[3]);
            $maxHigh = max($maxHigh, (float)$c[2]);
        }
        $this->info("Price Range: $minLow → $maxHigh");

        $profileRange = $maxHigh - $minLow;
        if ($profileRange <= 0) {
            $this->error("Invalid price range");
            return 1;
        }

        // 3️⃣ Создаём бины
        $binSize = $profileRange / $binsCount;
        $bins = [];
        for ($i = 0; $i < $binsCount; $i++) {
            $low = round($minLow + $i * $binSize, 6);
            $high = round($low + $binSize, 6);
            $bins[] = [
                'low' => $low,
                'high' => $high,
                'mid' => round(($low + $high) / 2, 6),
                'volume' => 0.0,
            ];
        }

        // 4️⃣ Распределяем объём свечей по бинам
        $vwapNumerator = 0.0;
        $vwapDenominator = 0.0;

        foreach ($candles as $c) {
            $low = (float)$c[3];
            $high = (float)$c[2];
            $close = (float)$c[4];
            $open = (float)$c[1];
            $volume = (float)$c[5];

            // Средняя цена свечи для VWAP
            $midPrice = ($low + $high + $close + $open) / 4;
            $vwapNumerator += $midPrice * $volume;
            $vwapDenominator += $volume;

            foreach ($bins as $idx => $bin) {
                if ($bin['high'] > $low && $bin['low'] < $high) {
                    $bins[$idx]['volume'] += $volume;
                }
            }
        }

        $vwap = $vwapDenominator > 0 ? $vwapNumerator / $vwapDenominator : 0;

        // 5️⃣ POC
        $pocBin = array_reduce($bins, fn($carry, $b) => (!$carry || $b['volume'] > $carry['volume']) ? $b : $carry, null);

        $this->info("POC (Point of Control): Mid {$pocBin['mid']} | Volume: " . number_format($pocBin['volume'], 2));
        $this->info("VWAP: " . round($vwap, 6));
        $this->line(str_repeat("-", 70));

        // 6️⃣ Топ-7 бинов
        usort($bins, fn($a, $b) => $b['volume'] <=> $a['volume']);
        $topBins = array_slice($bins, 0, 7);
        $this->info("TOP 7 VOLUME BINS:");
        foreach ($topBins as $b) {
            $this->line(
                "Bin {$b['low']} – {$b['high']} | Mid: {$b['mid']} | Volume: " .
                number_format($b['volume'], 2)
            );
        }

        // 7️⃣ Value Area (70% объёма)
        $totalVolume = array_sum(array_column($bins, 'volume'));
        $volumeThreshold = $totalVolume * 0.7;
        $volumeAccumulated = 0;
        $valueAreaBins = [];

        foreach ($bins as $bin) {
            $valueAreaBins[] = $bin;
            $volumeAccumulated += $bin['volume'];
            if ($volumeAccumulated >= $volumeThreshold) break;
        }

        $this->info("Value Area (70% of Volume):");
        foreach ($valueAreaBins as $b) {
            $this->line(
                "Bin {$b['low']} – {$b['high']} | Mid: {$b['mid']} | Volume: " .
                number_format($b['volume'], 2)
            );
        }

        // 8️⃣ ASCII-график профиля
        $this->info("ASCII Volume Profile:");
        $maxVol = max(array_column($bins, 'volume'));
        foreach ($bins as $b) {
            $barLength = $maxVol > 0 ? round(($b['volume'] / $maxVol) * 50) : 0;
            $bar = str_repeat("█", $barLength);
            $marker = ($b['mid'] == $pocBin['mid']) ? "<POC>" : "";
            $this->line(sprintf("%8.2f – %8.2f | %s %s", $b['low'], $b['high'], $bar, $marker));
        }

        return 0;
    }
}
