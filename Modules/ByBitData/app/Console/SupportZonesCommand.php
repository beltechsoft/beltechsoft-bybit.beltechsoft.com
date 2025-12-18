<?php

namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\ByBitData\Services\KlineService;

class SupportZonesCommand extends Command
{
    protected $signature = 'bybit:support-zone
                            {symbol : Символ инструмента}
                            {interval : Таймфрейм в минутах}
                            {limit : Количество свечей}
                            {--bins=20 : Количество диапазонов (bins)}';

    protected $description = 'Разбивает диапазон цен на равные части и считает касания свечей в каждом диапазоне';

    protected KlineService $klineService;

    public function __construct(KlineService $klineService)
    {
        parent::__construct();
        $this->klineService = $klineService;
    }

    public function handle(): int
    {
        $symbol   = $this->argument('symbol');
        $interval = (int) $this->argument('interval');
        $limit    = (int) $this->argument('limit');
        $bins     = (int) $this->option('bins');

        $this->info("Анализ: {$symbol} | TF {$interval}m | candles {$limit} | bins {$bins}");

        $candles = $this->klineService->getData($symbol, $interval, $limit);

        if ($candles->isEmpty()) {
            $this->error('Свечи не получены');
            return self::FAILURE;
        }

        $lowPrices = $candles->pluck('lowPrice');
        $highPrices = $candles->pluck('highPrice');

        $minPrice = $lowPrices->min();
        $maxPrice = $highPrices->max();

        if ($minPrice == $maxPrice) {
            $this->warn('Все цены одинаковые, нечего делить');
            return self::SUCCESS;
        }

        $step = ($maxPrice - $minPrice) / $bins;

        // Инициализация диапазонов
        $ranges = [];
        for ($i = 0; $i < $bins; $i++) {
            $ranges[] = [
                'min' => $minPrice + $i * $step,
                'max' => $minPrice + ($i + 1) * $step,
                'count' => 0,
            ];
        }
        $buffer = 0.0025;
        $zone = [];
        foreach ($candles as $candle) {
            $zone[] = ['min' => $candle['closePrice']  * (1 - $buffer), 'max' => $candle['closePrice'] * (1 + $buffer)];
        }


        foreach($candles as $candle) {
            foreach ($zone as $key => $item) {
                if ($candle['closePrice'] >= $item['min'] && $candle['closePrice'] <= $item['max']) {
                    $zone[$key]['count'] = Arr::get($zone, $key . '.count', 0) + 1 ;
                }
            }
        }



        return self::SUCCESS;
    }
}
