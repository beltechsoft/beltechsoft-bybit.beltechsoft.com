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


    public function handle()
    {
        $numCandles   = 3;   // Сколько последних закрытых свечей анализируем
        $volumeThresh = 2;   // Рост объёма в разах

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


        $candles = []; // массив свечей по символам

        while (true) {
            $message = $ws->receive();

            $data = json_decode($message, true);

            if (!isset($data['data'])) continue;

            $symbol = str_replace('kline.1.', '', $data['topic']);
            $candle  = array_first($data['data']);

            $candleEntity = CandleEntity::webService($candle +  ['symbol' => $symbol]);

            if ($candleEntity->confirm === true) {
                if(!$candleEntity->volume_usdt){
                    continue;
                }

                $candles[$candleEntity->symbol][] = $candleEntity->volume_usdt;

                if (count($candles[$candleEntity->symbol]) > $numCandles) {
                    array_shift($candles[$candleEntity->symbol]);
                }

                if($candleEntity->symbol === 'ETHUSDT'){
                    $this->info(collect($candles[$candleEntity->symbol])->map(function ($value){
                        return priceFormat($value);
                    })->join('/'));
                }

                if(count($candles[$candleEntity->symbol]) === $numCandles){

                    // Вывод информации по закрытой свече
                    $closedCandles = collect($candles[$candleEntity->symbol])->values();
                    $avgTurnover = $closedCandles->avg();
                    $volumeRatio = $avgTurnover > 0 ? $candleEntity->volume_usdt / $closedCandles->avg() : 0;


                    $isPump = $volumeRatio >= $volumeThresh && $candleEntity->priceChange >= 1;
                    $isDump = $volumeRatio >= $volumeThresh && $candleEntity->priceChange <= -1;
                    $volumeAvgFormat = priceFormat($avgTurnover);

                    $volumes = collect($candles[$candleEntity->symbol])->map(function ($value){
                        return priceFormat($value);
                    })->join(' / ');

                    if ($isPump || $isDump) {
                        $emoji = $isPump ? "🚀" : "💥";
                        $text = "{$emoji} " . ($isPump ? "PUMP" : "DUMP") . " detected on {$candleEntity->symbol} | AVG: {$volumeAvgFormat}  | Volume: {$volumes} | Volume ratio: ".round($volumeRatio,2)."x ";

                        $this->info($text);

                        // Отправка уведомления в Telegram
                        $this->sendTelegramNotification($text);
                    }
                }
            }
        }
    }




    /**
     * Отправка сообщения в Telegram
     */
    protected function sendTelegramNotification(string $message)
    {
        $botToken = config('services.telegram.bot_token');
        $chatId   = config('services.telegram.chat_id');

        Http::post("https://api.telegram.org/bot1333270563:AAFItPFP06IcajIASz9pO73M7jSdTFjkb5Q/sendMessage", [
            'chat_id' => '577008219',
            'text'    => $message,
            'parse_mode' => 'HTML',
        ]);
    }

}
