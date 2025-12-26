<?php namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\ByBitData\Entity\CandleEntity;
use Modules\ByBitData\Models\Ticker;
use WebSocket\Client;

class DetectPumpDumpCommand extends Command
{
    protected $signature = 'bybit:pump-dump';

    protected $description = 'Detect pump and dump on Bybit by volume';


    public function handle(): void
    {
        $numCandles   = 3;   // Сколько последних закрытых свечей анализируем
        $volumeThresh = 4;   // Рост объёма в разах

        $this->info("Bybit WebSocket monitor started. Analyzing last {$numCandles} closed candles...");

        $symbols = Ticker::pluck('symbol')->filter(fn($s) => strpos($s, '-') === false);
        $topics  = $symbols->map(fn($s) => "kline.1.$s")->toArray();

        if (empty($topics)) {
            $this->warn("No symbols found.");
            return;
        }

        $this->info("Subscribing to " . count($topics) . " symbols...");
        $this->line("Example topics: " . implode(', ', array_slice($topics, 0, 5)) . "...");

        $ws = new Client("wss://stream.bybit.com/v5/public/linear");

        $ws->send(json_encode([
            'op'   => 'subscribe',
            'args' => array_values($topics),
        ]));

        $candlesOpen = $candlesClosed = [];

        while (true) {
            $message = $ws->receive();

            $data = json_decode($message, true);

            if (!isset($data['data'])) continue;

            $symbol = str_replace('kline.1.', '', $data['topic']);
            $candle  = array_first($data['data']);

            $candleEntity = CandleEntity::webService($candle +  ['symbol' => $symbol]);

            if(!$candleEntity->volume_usdt){
                continue;
            }

            if(!Arr::has($candlesOpen, $candleEntity->symbol)){
                $candlesOpen[$candleEntity->symbol] = 0;
            }

            if(!Arr::has($candlesClosed, $candleEntity->symbol)){
                $candlesClosed[$candleEntity->symbol] = [];
            }

            if($candleEntity->confirm === false) {
                $candlesOpen[$candleEntity->symbol] = $candleEntity->volume_usdt;
            }

            if ($candleEntity->confirm === true) {
                $candlesClosed[$candleEntity->symbol][] = $candleEntity->volume_usdt;
            }


            if(count($candlesClosed[$candleEntity->symbol]) === $numCandles && Arr::has($candlesOpen, $candleEntity->symbol)) {

                // Вывод информации по закрытой свече
                $closedCandlesCollection = collect($candlesClosed[$candleEntity->symbol])->values();
                $closedCandlesAvg = $closedCandlesCollection->avg();
                $volumeRatio = $closedCandlesAvg > 0 ? $candlesOpen[$candleEntity->symbol] / $closedCandlesAvg : 0;


                $isPump = $volumeRatio >= $volumeThresh && $candleEntity->price_change >= 1;
                $isDump = $volumeRatio >= $volumeThresh && $candleEntity->price_change <= -1;

                $volumeAvgFormat = priceFormat($closedCandlesAvg);




                if ($isPump || $isDump) {

                    $volumes = collect(array_merge($candlesClosed[$candleEntity->symbol], [$candlesOpen[$candleEntity->symbol]]))->map(function ($value){
                        return priceFormat($value);
                    })->join(' / ');

                    $emoji = $isPump ? "🚀" : "💥";
                    $text = "{$emoji} " . ($isPump ? "PUMP" : "DUMP") . " detected on {$candleEntity->symbol}" . " | AVG: {$volumeAvgFormat}" . " | Price: {$candleEntity->current_price}" . " | Volume: {$volumes}" . " | Time: {$candleEntity->time}" . " | Volume ratio: ".round($volumeRatio,2)."x ";

                    $candlesClosed[$candleEntity->symbol] = [];

                    $this->info($text);

                    // Отправка уведомления в Telegram
                    $this->sendTelegramNotification($text);
                }
            }

            if (count($candlesClosed[$candleEntity->symbol]) > $numCandles) {
                array_shift($candlesClosed[$candleEntity->symbol]);
            }
        }
    }




    /**
     * Отправка сообщения в Telegram
     */
    protected function sendTelegramNotification(string $message)
    {
        Http::post("https://api.telegram.org/bot1333270563:AAFItPFP06IcajIASz9pO73M7jSdTFjkb5Q/sendMessage", [
            'chat_id' => '577008219',
            'text'    => $message,
            'parse_mode' => 'HTML',
        ]);
    }

}
