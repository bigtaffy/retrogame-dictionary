<?php

namespace App\Filament\Pages;

use App\Models\Console;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * 覆蓋率矩陣 — 一眼看 6 主機 × N 欄位的填充率。
 *
 * 為什麼這頁存在：
 *   日常 Catalog 工作 80% 是「補洞」(補翻譯、補封面、補簡介)。Default Filament
 *   只給單表 list view → 永遠在猜「現在哪個主機/哪個欄位最需要補」。這頁把它
 *   攤平成熱力圖，每格點下去 = 過濾後的 Games list（透過 query string）。
 */
class CoverageMatrixPage extends Page
{
    protected string $view = 'filament.pages.coverage-matrix-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '覆蓋率矩陣';

    protected static string|UnitEnum|null $navigationGroup = '營運監控 Ops';

    protected static ?string $title = '覆蓋率矩陣';

    protected static ?int $navigationSort = 10;

    /**
     * 表頭定義：哪些欄位要量、用什麼 SQL、點下去的 filter slug。
     * filter 名字必須跟 GamesTable 裡的 TernaryFilter::make() 一字不差。
     */
    public const FIELDS = [
        ['key' => 'title_zh',    'label' => '中文標題', 'filter' => 'missing_title_zh'],
        ['key' => 'title_jp',    'label' => '日文標題', 'filter' => 'missing_title_jp'],
        ['key' => 'overview_zh', 'label' => '中文簡介', 'filter' => 'missing_overview_zh'],
        ['key' => 'overview_en', 'label' => '英文簡介', 'filter' => 'missing_overview_en'],
        ['key' => 'cover',       'label' => '封面圖',   'filter' => 'missing_cover'],
        ['key' => 'screenshot',  'label' => '截圖',     'filter' => 'missing_screenshot'],
        ['key' => 'video',       'label' => '影片',     'filter' => 'missing_video'],
        ['key' => 'year',        'label' => '發行年',   'filter' => 'missing_year'],
        ['key' => 'maker',       'label' => '製作商',   'filter' => 'missing_maker'],
        ['key' => 'no_intro',    'label' => 'no_intro', 'filter' => 'missing_no_intro'],
        ['key' => 'genre',       'label' => '類型',     'filter' => 'missing_genre'],
    ];

    public function getMatrixData(): array
    {
        return Cache::remember('rgd:coverage_matrix', now()->addMinutes(5), function () {
            $consoles = Console::orderBy('id')->get(['id', 'slug', 'name']);
            $rows = [];

            foreach ($consoles as $c) {
                $total = (int) DB::table('games')->where('console_id', $c->id)->count();
                if ($total === 0) {
                    continue;
                }

                $row = ['console' => $c, 'total' => $total, 'cells' => []];

                foreach (self::FIELDS as $f) {
                    $row['cells'][$f['key']] = [
                        'filled' => $this->countField($c->id, $f['key']),
                        'total' => $total,
                    ];
                }
                $rows[] = $row;
            }

            return $rows;
        });
    }

    /**
     * 真正的 schema（02-Database-Schema.md）：
     *   titles.language       char(3): 'zh' / 'jp' / 'en'
     *   descriptions.kind     enum: 'overview', 'comment', 'plot', 'gameplay', 'review'
     *   descriptions.language char(3): 'zh' / 'en' / 'jp'
     *   images.kind           enum: 'cover' / 'snap' / 'title_screen' / ...
     *   注意 cover 是 enum 值之一，但「主封面」是 games.cover_image_id 指過去那筆
     */
    private function countField(int $consoleId, string $key): int
    {
        return match ($key) {
            'title_zh' => DB::table('titles')
                ->join('games', 'games.id', '=', 'titles.game_id')
                ->where('games.console_id', $consoleId)
                ->where('titles.language', 'zh')
                ->where('titles.is_aka', false)
                ->distinct('titles.game_id')
                ->count('titles.game_id'),

            'title_jp' => DB::table('titles')
                ->join('games', 'games.id', '=', 'titles.game_id')
                ->where('games.console_id', $consoleId)
                ->where('titles.language', 'jp')
                ->where('titles.is_aka', false)
                ->distinct('titles.game_id')
                ->count('titles.game_id'),

            'overview_zh' => DB::table('descriptions')
                ->join('games', 'games.id', '=', 'descriptions.game_id')
                ->where('games.console_id', $consoleId)
                ->where('descriptions.kind', 'overview')
                ->where('descriptions.language', 'zh')
                ->whereNotNull('descriptions.text')
                ->where('descriptions.text', '!=', '')
                ->distinct('descriptions.game_id')
                ->count('descriptions.game_id'),

            'overview_en' => DB::table('descriptions')
                ->join('games', 'games.id', '=', 'descriptions.game_id')
                ->where('games.console_id', $consoleId)
                ->where('descriptions.kind', 'overview')
                ->where('descriptions.language', 'en')
                ->whereNotNull('descriptions.text')
                ->where('descriptions.text', '!=', '')
                ->distinct('descriptions.game_id')
                ->count('descriptions.game_id'),

            'cover' => DB::table('games')
                ->where('console_id', $consoleId)
                ->whereNotNull('cover_image_id')
                ->count(),

            'screenshot' => DB::table('images')
                ->join('games', 'games.id', '=', 'images.game_id')
                ->where('games.console_id', $consoleId)
                ->where('images.kind', 'snap')      // libretro 命名沿用 snap
                ->distinct('images.game_id')
                ->count('images.game_id'),

            'video' => DB::table('videos')
                ->join('games', 'games.id', '=', 'videos.game_id')
                ->where('games.console_id', $consoleId)
                ->distinct('videos.game_id')
                ->count('videos.game_id'),

            'year' => DB::table('games')
                ->where('console_id', $consoleId)
                ->whereNotNull('release_year')
                ->count(),

            'maker' => DB::table('games')
                ->where('console_id', $consoleId)
                ->whereNotNull('maker')
                ->where('maker', '!=', '')
                ->count(),

            'no_intro' => DB::table('games')
                ->where('console_id', $consoleId)
                ->whereNotNull('no_intro_name')
                ->count(),

            'genre' => DB::table('game_genres')
                ->join('games', 'games.id', '=', 'game_genres.game_id')
                ->where('games.console_id', $consoleId)
                ->distinct('game_genres.game_id')
                ->count('game_genres.game_id'),

            default => 0,
        };
    }

    /** 0..1 → CSS class，配合 theme.css 的 .rgd-cell-* */
    public static function cellClass(float $ratio): string
    {
        return match (true) {
            $ratio >= 0.99 => 'rgd-cell-full',
            $ratio >= 0.80 => 'rgd-cell-ok',
            $ratio >= 0.50 => 'rgd-cell-warn',
            $ratio > 0     => 'rgd-cell-bad',
            default        => 'rgd-cell-empty',
        };
    }

    public function refreshAction(): Action
    {
        return Action::make('refresh')
            ->label('重新計算')
            ->icon(Heroicon::ArrowPath)
            ->color('info')
            ->action(function () {
                Cache::forget('rgd:coverage_matrix');
                $this->dispatch('$refresh');
                Notification::make()
                    ->title('已重新計算覆蓋率')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->refreshAction()];
    }
}
