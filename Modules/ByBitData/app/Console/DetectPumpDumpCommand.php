<?php namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Modules\ByBitData\Entity\klineEntity;
use Modules\ByBitData\Models\Ticker;
use WebSocket\Client;

class DetectPumpDumpCommand extends Command
{
    protected $signature = 'bybit:pump-dump';

    protected $description = 'Detect pump and dump on Bybit by volume';


    public function handle(): void
    {
        $symbols = Ticker::pluck('symbol')->values();
        $limit = 4;
       // $symbols = ['BTCUSDT'];
        $lines = [];
        foreach ($symbols as $symbol) {

            $response = Http::get('https://api.bybit.com/v5/market/kline', [
                'category' => 'linear',
                'symbol'   => $symbol,
                'interval' => '240',
                'limit'    => 5,
            ]);
            $candles = Arr::get($response->json(), 'result.list', []);
            array_shift($candles);

            $collection = collect($candles)->map(fn($candle) => KlineEntity::makeHttp($candle, $symbol));

            //Проверяем, последние свечи идет ли сейчас тред

            $direction = $collection->pluck('direction')->unique()->count() === 1 ? $collection->first()->direction : null;
            $sumPercentChange = $collection->sum('price_change');

            if($direction and $sumPercentChange > 10){
                $emoji = $direction === 'up' ? '🚀' : ($direction === 'down' ? '💥' : '➖');

                // Ссылка на график TradingView
                $chartUrl = "https://www.tradingview.com/chart/?symbol=BYBIT:{$symbol}&interval=240";
                $lines[] = "{$emoji} <b>{$symbol}</b> - {$sumPercentChange}% - <a href=\"{$chartUrl}\">Chart</a>";

            }

        }

        $message = implode("\n", $lines);

        Http::post("https://api.telegram.org/bot8394746885:AAHDT4I7cx4Uha7VWSuBXpzvsF1euXyVSbg/sendMessage", [
            'chat_id'    => '577008219',
            'text'       => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => false,
        ]);
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
