{{-- 資料來源 dashboard --}}
<x-filament-panels::page>
    @php $sources = $this->getSources(); @endphp

    @if ($sources->isEmpty())
        <x-filament::section>
            <div class="text-center py-8 space-y-4">
                <p class="text-gray-300">還沒登錄任何外部資料來源。</p>
                <p class="text-xs text-gray-400">
                    libretro-thumbnails、TheGamesDB、ScreenScraper、Wikipedia (zh/en/jp)
                </p>
                {{ $this->seedAction }}
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($sources as $s)
                @php
                    $statusClass = match ($s->status) {
                        'ok' => 'rgd-status-ok',
                        'warning' => 'rgd-status-warn',
                        'error' => 'rgd-status-error',
                        default => 'rgd-status-stale',
                    };
                    $stale = $s->isStale();
                @endphp
                <div class="rounded-xl border border-white/10 bg-[rgba(20,16,42,0.85)] p-4 space-y-3
                            transition hover:border-primary-500/40">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-gray-400">{{ $s->kind }}</div>
                            <div class="font-semibold text-base text-white">{{ $s->name }}</div>
                            <div class="text-xs font-mono text-gray-500">{{ $s->slug }}</div>
                        </div>
                        <div class="text-2xl {{ $statusClass }}">●</div>
                    </div>

                    <div class="text-xs space-y-1 text-gray-300">
                        <div class="flex justify-between">
                            <span>狀態：</span>
                            <span>{{ $s->status_badge }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>最後同步：</span>
                            <span class="{{ $stale ? 'text-warning-400' : '' }}">
                                {{ $s->last_synced_at?->diffForHumans() ?? '從未' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>成功 / 失敗：</span>
                            <span class="font-mono">
                                <span class="text-success-400">{{ $s->success_count }}</span>
                                /
                                <span class="text-danger-400">{{ $s->fail_count }}</span>
                            </span>
                        </div>
                    </div>

                    @if ($s->last_error)
                        <div class="text-xs text-danger-400 bg-danger-500/10 rounded p-2 font-mono break-all">
                            {{ \Illuminate\Support\Str::limit($s->last_error, 150) }}
                        </div>
                    @endif

                    <div class="flex gap-2 pt-2 border-t border-white/5">
                        <x-filament::button
                            size="xs"
                            color="info"
                            icon="heroicon-m-arrow-path"
                            wire:click="mountAction('triggerSync', @js(['id' => $s->id]))">
                            立刻同步
                        </x-filament::button>

                        <x-filament::button
                            size="xs"
                            color="warning"
                            icon="heroicon-m-power"
                            wire:click="mountAction('toggleEnabled', @js(['id' => $s->id]))">
                            {{ $s->enabled ? '停用' : '啟用' }}
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 圖例 --}}
        <div class="mt-6 flex flex-wrap gap-4 text-xs text-gray-400">
            <span><span class="rgd-status-ok">●</span> 正常</span>
            <span><span class="rgd-status-warn">●</span> 警告</span>
            <span><span class="rgd-status-error">●</span> 異常</span>
            <span><span class="rgd-status-stale">●</span> 久未同步 / 未知</span>
        </div>
    @endif
</x-filament-panels::page>
