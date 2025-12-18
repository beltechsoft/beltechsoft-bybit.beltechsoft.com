<?php namespace Modules\ByBitData\Services;

use Illuminate\Support\Facades\Http;

class KlineService
{

    /**
     * Получает и нормализует свечные данные (kline) с Bybit API
     * и рассчитывает универсальные метрики свечи.
     *
     * Метод предназначен как базовый источник данных для:
     * - анализа поддержки / сопротивления
     * - определения силы свечи
     * - фильтрации ложных касаний
     * - построения торговых зон и паттернов
     *
     * Источник данных:
     * Bybit REST API v5 /market/kline
     *
     * Формат возвращаемых данных:
     * Каждая свеча представлена в виде ассоциативного массива со следующими полями:
     *
     * --- Исходные данные (как в документации Bybit) ---
     * @field int    startTime   Время начала свечи в миллисекундах (Unix ms)
     * @field float  openPrice   Цена открытия
     * @field float  highPrice   Максимальная цена
     * @field float  lowPrice    Минимальная цена
     * @field float  closePrice  Цена закрытия
     * @field float  volume      Торговый объём
     * @field float  turnover    Оборот
     *
     * --- Вычисляемые поля ---
     * @field string direction   Направление свечи:
     *                           - bullish  (closePrice > openPrice)
     *                           - bearish  (closePrice < openPrice)
     *                           - neutral  (closePrice == openPrice)
     *
     * @field float  range       Полный диапазон свечи (highPrice - lowPrice)
     * @field float  bodySize    Размер тела свечи (|closePrice - openPrice|)
     * @field float  bodyPercent Доля тела свечи относительно диапазона (bodySize / range)
     * @field float  upperWick   Размер верхней тени
     * @field float  lowerWick   Размер нижней тени
     *
     * @field bool   isStrong    Флаг "сильной" свечи:
     *                           true, если тело занимает более 60% диапазона
     *
     * Особенности:
     * - Данные от Bybit возвращаются в обратном порядке (от новых к старым)
     * - Метод автоматически переворачивает массив в хронологический порядок
     *
     * @param  string $symbol   Торговый символ (например: BTCUSDT, ETHUSDT)
     * @param  int    $interval Таймфрейм свечей в минутах (Bybit interval)
     * @param  int    $limit    Количество запрашиваемых свечей
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     startTime:int,
     *     openPrice:float,
     *     highPrice:float,
     *     lowPrice:float,
     *     closePrice:float,
     *     volume:float,
     *     turnover:float,
     *     direction:string,
     *     range:float,
     *     bodySize:float,
     *     bodyPercent:float,
     *     upperWick:float,
     *     lowerWick:float,
     *     isStrong:bool
     * }>
     */
    public function getData(string $symbol, int $interval = 240, int $limit = 90): \Illuminate\Support\Collection
    {

        $response = Http::get("https://api.bybit.com/v5/market/kline", [
            'symbol'   => $symbol,
            'interval' => $interval,
            'limit'    => $limit,
            'category' => 'linear',
        ]);

        $raw = $response->json()['result']['list'] ?? [];

        $candles = array_map(function (array $list) {

            $open  = (float) $list[1];
            $high  = (float) $list[2];
            $low   = (float) $list[3];
            $close = (float) $list[4];
            $startTime = (int)$list[0];

            $range     = max($high - $low, 0.00000001);
            $bodySize  = abs($close - $open);
            $upperWick = $high - max($open, $close);
            $lowerWick = min($open, $close) - $low;

            return [
                // данные Bybit (как в доке)
                'startTime'  => $startTime,
                'startTimeFormat'  => date('Y-m-d H:i:s', ($startTime/1000)),
                'openPrice'  => $open,
                'highPrice'  => $high,
                'lowPrice'   => $low,
                'closePrice' => $close,
                'volume'     => (float) $list[5],
                'turnover'   => (float) $list[6],

                // направление
                'direction' => $close > $open
                    ? 'bullish'
                    : ($close < $open ? 'bearish' : 'neutral'),

                // универсальные метрики
                'range'        => $range,
                'bodySize'     => $bodySize,
                'bodyPercent'  => $bodySize / $range,
                'upperWick'    => $upperWick,
                'lowerWick'    => $lowerWick,

                // сильная свеча (универсальный критерий)
                'isStrong' => $bodySize / $range > 0.6,
            ];

        }, $raw);

        // Bybit → от новых к старым
        return collect(array_reverse($candles));
    }
}
