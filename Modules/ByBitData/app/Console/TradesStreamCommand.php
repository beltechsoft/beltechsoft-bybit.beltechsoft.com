<?php

namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Modules\ByBitData\Models\Trade;
use Modules\ByBitData\Services\TickerService;
use WebSocket\Client;

class TradesStreamCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bybit:trades-stream';

    /**
     * The console command description.
     */
    protected $description = 'Получаем данные от Байбат по курсам';
    private TickerService $tickerService;

    /**
     * Create a new command instance.
     */
    public function __construct(TickerService $tickerService)
    {
        parent::__construct();
        $this->tickerService = $tickerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Connecting to Bybit Linear Futures trade WebSocket...");

        $ws = new Client("wss://stream.bybit.com/v5/public/linear", [
            'timeout' => 60,
        ]);

        $subscribe = [
            "op" => "subscribe",
            "args" => ["publicTrade.ETHUSDT", "publicTrade.BTCUSDT", 'publicTrade.SOLUSDT', 'publicTrade.ASTERUSDT'],
        ];
        $ws->send(json_encode($subscribe));
        $this->info("Sent subscribe: " . json_encode($subscribe));

        $buffer = []; // для batch insert
        $batchSize = 200;

        while (true) {

            try {
                $msg = $ws->receive();
                $data = json_decode($msg, true);
                if (!$data) continue;

                // Ответ на ping
                if (isset($data['op']) && $data['op'] === 'ping') {
                    $ws->send(json_encode(['op' => 'pong']));
                    continue;
                }

                // Обрабатываем snapshot с массивом сделок
                if (isset($data['topic']) && isset($data['data']['0'])) {
                    $trades = $data['data'];
                    foreach ($trades as $trade) {
                        // Фильтруем по объёму > 0.5

                        $direction = $trade['S'] === 'Buy' ? 1 : 0;
                        $tickDirMap = ['PlusTick' => 1, 'MinusTick' => 2, 'ZeroPlusTick' => 3];
                        $tick_direction = $tickDirMap[$trade['L']] ?? null;

                        $buffer[] = [
                            'timestamp_ms' => $trade['T'],
                            'symbol' => $this->tickerService->all()->firstWhere('symbol', $trade['s'])->id,
                            'direction' => $direction,
                            'price' => $trade['p'],
                            'volume' => $trade['v'],
                            'volume_usd' => $trade['p'] * $trade['v'],
                            'tick_direction' => $tick_direction,
                            'trade_id' => $trade['i'],
                            'buy_taker' => $trade['BT'],
                            'rpi' => $trade['RPI'],
                            'seq' => $trade['seq'],
                        ];
                    }

                    // Batch insert каждые $batchSize записей
                    if (count($buffer) >= $batchSize) {
                        Trade::upsert($buffer, ['trade_id'], [
                            'timestamp_ms', 'direction', 'price', 'volume', 'tick_direction', 'buy_taker', 'rpi', 'seq'
                        ]);
                        $this->info("Inserted batch of " . count($buffer) . " trades.");
                        $buffer = [];
                    }
                }

            } catch (ConnectionException $e) {
                if (str_contains($e->getMessage(), 'Client read timeout')) {
                    continue;
                }
                $this->error("WebSocket error: " . $e->getMessage());
                break;
            } catch (\Exception $e) {
                $this->error("Unexpected error: " . $e->getMessage());
                break;
            }
        }

        // Вставка оставшихся сделок
        if (count($buffer) > 0) {
            Trade::upsert($buffer, ['trade_id'], [
                'timestamp_ms', 'direction', 'price', 'volume', 'tick_direction', 'buy_taker', 'rpi', 'seq'
            ]);
            $this->info("Inserted final batch of " . count($buffer) . " trades.");
        }

        return 0;
    }

}
