<?php namespace Modules\ByBitData\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\ByBitData\Models\Ticker;
use Modules\ByBitData\Services\TickerService;
use Symfony\Component\Console\Input\InputOption;

class TickerCommand extends Command
{
    protected $signature = 'bybit:ticker {--category=linear : Категория тикеров (spot, linear, inverse)}';

    protected $description = 'Получаем информацию о котировках';

    public function __construct(TickerService $tickerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $category = $this->option('category');

        $response = Http::timeout(10)->get('https://api.bybit.com/v5/market/tickers', ['category' => $category]);

        if (!$response->successful()) {
            $this->error('Bybit API error');
            return Command::FAILURE;
        }

        $tickers = $response->json()['result']['list'] ?? [];

        if (empty($tickers)) {
            $this->warn('No tickers found');
            return Command::SUCCESS;
        }

        foreach ($tickers as $ticker) {
            Ticker::updateOrCreate(['symbol' => $ticker['symbol']], [
                'last_price' => $ticker['lastPrice'],
            ]);
        }

        return Command::SUCCESS;
    }

    protected function getOptions(): array
    {
        return [
            ['category', null, InputOption::VALUE_OPTIONAL, 'Категория тикеров (spot, linear, inverse)', 'spot'],
        ];
    }
}
