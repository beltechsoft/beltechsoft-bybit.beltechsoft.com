<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ByBitData\Models\Trade;
use WebSocket\Client;

class BybitTicksTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "bybit:test";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function handle()
    {
        $this->info("Connecting to Bybit Linear Futures trade WebSocket...");

        $ws = new Client("wss://stream.bybit.com/v5/public/linear", [
            'timeout' => 60,
        ]);

        $subscribe = [
            "op" => "subscribe",
            "args" => ["publicTrade.ETHUSDT", "publicTrade.BTCUSDT"],
        ];
        $ws->send(json_encode($subscribe));
        $this->info("Sent subscribe: " . json_encode($subscribe));

        $buffer = []; // для batch insert
        $batchSize = 2;

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
                        //if ((float)$trade['v'] <= 0.5) continue;

                        // Конвертируем в числа
                        $direction = $trade['S'] === 'Buy' ? 1 : 0;
                        $tickDirMap = ['PlusTick' => 1, 'MinusTick' => 2, 'ZeroPlusTick' => 3];
                        $tick_direction = $tickDirMap[$trade['L']] ?? null;

                        $buffer[] = [
                            'timestamp_ms' => $trade['T'],
                            'symbol' => $trade['s'],
                            'direction' => $direction,
                            'price' => $trade['p'],
                            'volume' => $trade['v'],
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
