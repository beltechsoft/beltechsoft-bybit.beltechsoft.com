
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
                                    <td width=20 class="px-3 py-1 whitespace-nowrap text-sm font-medium ">
                                        {{ \Carbon\Carbon::createFromTimestampMs($item->tf_ms)->format('d-m, H:i') }}</td>
                                    <td class="px-3 py-1 whitespace-nowrap text-sm ">
                                        @php
                                            $buyVolume = $item->buy_volume_usd;
                                            $sellVolume = $item->sell_volume_usd;

                                            $buyTicks = $item->buy_ticks_count;
                                            $sellTicks = $item->sell_ticks_count;

                                            $percentDiffVolume = volumePercentDiff($buyVolume, $sellVolume);
                                            $percentDiffTicks = volumePercentDiff($buyTicks, $sellTicks);

                                            // Победитель для объёмов
                                            $buyVolumeClass = $buyVolume > $sellVolume ? 'text-green-600' : '';
                                            $sellVolumeClass = $sellVolume > $buyVolume ? 'text-red-600' : '';

                                            // Победитель для тикетов
                                            $buyTicksClass = $buyTicks > $sellTicks ? 'text-green-600' : '';
                                            $sellTicksClass = $sellTicks > $buyTicks ? 'text-red-600' : '';
                                        @endphp

                                            <!-- Вывод -->
                                        <div>
                                            <!-- Объёмы -->
                                            <span class="{{ $buyVolumeClass }}">{{ priceFormat($buyVolume) }}</span> /
                                            <span class="{{ $sellVolumeClass }}">{{ priceFormat($sellVolume) }}</span>
                                            &ndash; {{ $percentDiffVolume }}<br>

                                            <!-- Тикеты -->
                                            <span class="{{ $buyTicksClass }}">{{ priceFormat($buyTicks) }}</span> /
                                            <span class="{{ $sellTicksClass }}">{{ priceFormat($sellTicks) }}</span>
                                            &ndash; {{ $percentDiffTicks }}<br>

                                            <!-- Всего -->
                                            {{ priceFormat($item->total_volume_usd) }} /
                                            {{ priceFormat($item->total_ticks_count) }}
                                        </div>
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
