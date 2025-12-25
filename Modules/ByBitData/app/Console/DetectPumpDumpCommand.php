<?php namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\ByBitData\Models\Ticker;
use WebSocket\Client;

class DetectPumpDumpCommand extends Command
{
    protected $signature = 'bybit:pump-dump';

    protected $description = 'Detect pump and dump on Bybit by volume';


    public function handle()
    {
        $this->info('Bybit trade monitor started...');

        while (true) {
            $symbols = Ticker::pluck('symbol')->filter(fn($s) => strpos($s, '-') === false);

            foreach ($symbols as $symbol) {
                $response = Http::get('https://api.bybit.com/v5/market/kline', [
                    'category' => 'linear',
                    'symbol'   => $symbol,
                    'interval' => 1,
                    'limit'    => 12, // берём только 12 последних свечей
                ]);

                if (!$response->ok()) {
                    $this->warn("API error: $symbol");
                    continue;
                }

                $klines = collect($response->json('result.list'))->reverse()->values();

                if ($klines->count() < 12) {
                    $this->warn("Not enough candles for $symbol");
                    continue;
                }

                $lastCandle = $klines->last();
                $avgTurnover = $klines->avg(fn($k) => (float)$k[6]);
                $currentTurnover = (float)$lastCandle[6];
                $volumeRatio = $avgTurnover > 0 ? $currentTurnover / $avgTurnover : 0;

                $open  = (float)$lastCandle[1];
                $close = (float)$lastCandle[4];
                $priceChange = ($close - $open) / $open * 100;

                $isPump = $volumeRatio >= 4 && $priceChange >= 1;
                $isDump = $volumeRatio >= 4 && $priceChange <= -1;

                $startTime = \Carbon\Carbon::createFromTimestampMs($lastCandle[0])->toDateTimeString();
                $endTime   = \Carbon\Carbon::createFromTimestampMs($lastCandle[0] + 60000 - 1)->toDateTimeString();
                $timeInfo  = "Start: {$startTime} | End: {$endTime}";

                if ($isPump || $isDump) {
                    $emoji = $isPump ? "🚀" : "💥";
                    $text = "{$emoji} " . ($isPump ? "PUMP" : "DUMP") . " detected on {$symbol} | Price: {$close} | Change: ".round($priceChange,2)."% | Volume ratio: ".round($volumeRatio,2)."x | {$timeInfo}";

                    $this->info($text);

                    // Отправка уведомления в Telegram
                    $this->sendTelegramNotification($text);
                }
            }

            // Пауза между итерациями, чтобы не перегружать API
            sleep(30); // 30 секунд, можно менять
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
