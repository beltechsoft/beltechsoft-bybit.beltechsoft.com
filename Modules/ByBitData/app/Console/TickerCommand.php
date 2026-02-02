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
        $now = now();

        foreach ($tickers as $ticker) {
            if(str_contains($ticker['symbol'], '-')){
                continue;
            }
            Ticker::updateOrCreate(['symbol' => $ticker['symbol']], [
                'last_price'            => $ticker['lastPrice'] ?? null,
                'index_price'           => $ticker['indexPrice'] ?? null,
                'mark_price'            => $ticker['markPrice'] ?? null,
                'prev_price_24h'        => $ticker['prevPrice24h'] ?? null,
                'price_24h_pcnt'        => $ticker['price24hPcnt'] ?? null,
                'high_price_24h'        => $ticker['highPrice24h'] ?? null,
                'low_price_24h'         => $ticker['lowPrice24h'] ?? null,
                'prev_price_1h'         => $ticker['prevPrice1h'] ?? null,
                'open_interest'         => $ticker['openInterest'] ?? null,
                'open_interest_value'   => $ticker['openInterestValue'] ?? null,
                'turnover_24h'          => $ticker['turnover24h'] ?? null,
                'volume_24h'            => $ticker['volume24h'] ?? null,
                'funding_rate'          => $ticker['fundingRate'] ?? null,
                'next_funding_time'     => $ticker['nextFundingTime'] ?? null,
                'ask1_size'             => $ticker['ask1Size'] ?? null,
                'bid1_price'            => $ticker['bid1Price'] ?? null,
                'ask1_price'            => $ticker['ask1Price'] ?? null,
                'bid1_size'             => $ticker['bid1Size'] ?? null,
                'cur_pre_listing_phase' => $ticker['curPreListingPhase'] ?? null,
                'funding_interval_hour' => $ticker['fundingIntervalHour'] ?? null,
                'funding_cap'           => $ticker['fundingCap'] ?? null,
            ]);
        }

        Ticker::where('updated_at', '<', $now)->delete();

        return Command::SUCCESS;
    }

    protected function getOptions(): array
    {
        return [
            ['category', null, InputOption::VALUE_OPTIONAL, 'Категория тикеров (spot, linear, inverse)', 'spot'],
        ];
    }
}
