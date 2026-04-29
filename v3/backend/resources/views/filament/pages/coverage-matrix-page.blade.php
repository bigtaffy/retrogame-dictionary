{{-- 覆蓋率矩陣 — 主機 × 欄位 熱力圖 --}}
<x-filament-panels::page>
    @php
        $rows = $this->getMatrixData();
        $fields = \App\Filament\Pages\CoverageMatrixPage::FIELDS;
    @endphp

    @if (empty($rows))
        <x-filament::section>
            <p class="text-sm text-gray-300">
                還沒有任何主機資料。先跑 <code>php artisan db:seed --class=ConsolesSeeder</code> 與
                <code>php artisan rgd:import-json</code>。
            </p>
        </x-filament::section>
    @else
        <div class="overflow-x-auto rounded-xl border border-white/5 bg-[rgba(20,16,42,0.85)] p-1">
            <table class="w-full border-separate border-spacing-1 text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-[rgba(20,16,42,0.95)] p-2 text-left text-xs uppercase tracking-wider text-gray-300">
                            主機 / 欄位
                        </th>
                        @foreach ($fields as $f)
                            <th class="p-2 text-xs uppercase tracking-wider text-gray-300 whitespace-nowrap">
                                {{ $f['label'] }}
                            </th>
                        @endforeach
                        <th class="p-2 text-xs uppercase tracking-wider text-gray-300">總數</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="sticky left-0 z-10 bg-[rgba(20,16,42,0.95)] p-2 font-semibold whitespace-nowrap">
                                <span class="text-primary-400">{{ strtoupper($row['console']->slug) }}</span>
                                <span class="ml-2 text-xs text-gray-400">{{ $row['console']->name }}</span>
                            </td>
                            @foreach ($fields as $f)
                                @php
                                    $cell = $row['cells'][$f['key']];
                                    $ratio = $row['total'] > 0 ? $cell['filled'] / $row['total'] : 0;
                                    $pct = round($ratio * 100);
                                    $class = \App\Filament\Pages\CoverageMatrixPage::cellClass($ratio);
                                    // TernaryFilter 的 value: '1'=true(缺), '0'=false(已填)。
                                    // SelectFilter（console_id）直接帶 id。
                                    $base = \App\Filament\Resources\Games\GameResource::getUrl('index');
                                    $href = $base.'?'.http_build_query([
                                        'tableFilters[console_id][value]' => $row['console']->id,
                                        'tableFilters['.$f['filter'].'][value]' => '1',
                                    ]);
                                @endphp
                                <td class="p-0">
                                    <a href="{{ $href }}"
                                       title="點擊查看缺{{ $f['label'] }}的 {{ strtoupper($row['console']->slug) }} 遊戲"
                                       class="block rounded-md px-2 py-3 text-center font-mono text-xs transition hover:scale-105 hover:shadow-lg {{ $class }}">
                                        <div class="font-bold text-base">{{ $pct }}%</div>
                                        <div class="opacity-75">{{ number_format($cell['filled']) }} / {{ number_format($row['total']) }}</div>
                                    </a>
                                </td>
                            @endforeach
                            <td class="p-2 text-center font-mono text-info-300">{{ number_format($row['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 text-xs text-gray-300">
            <span class="rgd-cell-full inline-block px-3 py-1 rounded">≥ 99%</span>
            <span class="rgd-cell-ok inline-block px-3 py-1 rounded">≥ 80%</span>
            <span class="rgd-cell-warn inline-block px-3 py-1 rounded">≥ 50%</span>
            <span class="rgd-cell-bad inline-block px-3 py-1 rounded">&lt; 50%</span>
            <span class="rgd-cell-empty inline-block px-3 py-1 rounded">0%</span>
        </div>
    @endif
</x-filament-panels::page>
