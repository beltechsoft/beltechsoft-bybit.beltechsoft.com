
<x-layouts.app :title="__('Dashboard')">
    <div class="inline-flex rounded-lg shadow-2xs">
        @foreach($symbols as $symbol)
            <a href="?filter[symbol]={{$symbol->id}}" type="button" class="py-3 px-4 inline-flex items-center gap-x-2 -ms-px first:rounded-s-lg first:ms-0 last:rounded-e-lg text-sm font-medium focus:z-10 border border-gray-200 bg-white shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none">
                {{$symbol->symbol}}
            </a>
        @endforeach
    </div>
    <br>
    <br>
    {{$symbols->firstWhere('id', request('filter.symbol'))->symbol}}
    <div class="flex flex-col">
        <div class="-m-1.5 overflow-x-auto">
            <div class="p-1.5 min-w-full inline-block align-middle">
                @foreach(['tf15' => $tf15, 'tf4h' =>$tf4h] as $key => $value)
                    <h1 class="text-center text-3xl">{{$key}}</h1>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                            <tr>

                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            @foreach($value as $k => $item)
                                <tr>
                                    <td width="20" class="px-3 py-1 whitespace-nowrap text-sm font-medium ">{{++$k}}</td>
                                    <td width=20 class="px-3 py-1 whitespace-nowrap text-sm font-medium ">{{$item->tf_15m}}</td>
                                    <td class="px-3 py-1 whitespace-nowrap text-sm ">
                                        @php
                                            $buy = $item->buy_volume_usd;
                                            $sell = $item->sell_volume_usd;

                                            $percentDiff = volumePercentDiff($buy, $sell);
                                            $percentDiffTike = volumePercentDiff($item->buy_ticks_count, $item->sell_ticks_count);

                                            // Определяем победителя
                                            $buyClass = '';
                                            $sellClass = '';

                                            if ($buy > $sell) {
                                                $buyClass = 'text-green-600';
                                            } elseif ($sell > $buy) {
                                                $sellClass = 'text-red-600';
                                            }
                                        @endphp

                                            <span class="{{ $buyClass }}">{{ priceFormat($buy)  }}</span> / <span class="{{ $sellClass }}">{{ priceFormat($sell) }}</span> &ndash; {{ $percentDiff }}<br>
                                            <span class="{{ $buyClass }}">{{ priceFormat($item->buy_ticks_count)  }}</span> / <span class="{{ $sellClass }}">{{ priceFormat($item->sell_ticks_count) }}</span>&ndash; {{$percentDiffTike}}<br>
                                            {{ priceFormat($item->total_volume_usd)}} / {{priceFormat($item->total_ticks_count) }}
                                        </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
