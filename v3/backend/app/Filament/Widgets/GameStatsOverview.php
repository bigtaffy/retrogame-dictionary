<?php

namespace App\Filament\Widgets;

use App\Models\Console;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GameStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return Console::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Console $c) => Stat::make($c->name_zh, number_format($c->game_count_cached))
                ->description('slug: '.$c->slug)
            )
            ->all();
    }
}
