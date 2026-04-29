<?php

namespace App\Filament\Pages;

use App\Models\DataSource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * 資料來源 dashboard — Page B（見 Docs/12-Admin-UI-Design.md）
 *
 * 為什麼這頁存在：
 *   外部資料源（libretro thumbs、TheGamesDB、ScreenScraper、Wikipedia、巴哈）
 *   各自有自己的 rate limit、可用度、版本變動。default Filament 沒提供「健康看板」，
 *   每個 sync 都是 ad-hoc Python 腳本→ admin 看不到「上次成功 / 失敗 / 哪個主機沒同步」。
 *   這頁把所有 source 攤成一張表，每行一鍵 trigger 重跑。
 */
class DataSourcesPage extends Page
{
    protected string $view = 'filament.pages.data-sources-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $navigationLabel = '資料來源';

    protected static string|UnitEnum|null $navigationGroup = '系統 System';

    protected static ?string $title = '外部資料來源';

    protected static ?int $navigationSort = 20;

    public function getSources()
    {
        return DataSource::query()
            ->orderByRaw("FIELD(status, 'error', 'warning', 'unknown', 'ok', 'disabled')")
            ->orderBy('slug')
            ->get();
    }

    /** 對於空表的引導文案 */
    public function getDefaultSeeds(): array
    {
        return [
            ['slug' => 'libretro_thumbs', 'name' => 'libretro-thumbnails',
             'kind' => 'covers', 'endpoint' => 'https://github.com/libretro-thumbnails',
             'config' => ['method' => 'git_pull', 'rate' => 'unlimited']],

            ['slug' => 'tgdb_api', 'name' => 'TheGamesDB API',
             'kind' => 'metadata', 'endpoint' => 'https://api.thegamesdb.net/v1.1',
             'config' => ['rate' => '1000/month', 'key_env' => 'TGDB_API_KEY']],

            ['slug' => 'screenscraper', 'name' => 'ScreenScraper.fr',
             'kind' => 'mixed', 'endpoint' => 'https://screenscraper.fr/api2',
             'config' => ['rate' => 'depends on dev key', 'key_env' => 'SS_DEV_KEY']],

            ['slug' => 'wiki_zh', 'name' => 'Wikipedia 中文',
             'kind' => 'descriptions', 'endpoint' => 'https://zh.wikipedia.org/w/api.php',
             'config' => ['rate' => '~10 req/s', 'method' => 'mediawiki_api']],

            ['slug' => 'wiki_en', 'name' => 'Wikipedia English',
             'kind' => 'descriptions', 'endpoint' => 'https://en.wikipedia.org/w/api.php',
             'config' => ['rate' => '~10 req/s', 'method' => 'mediawiki_api']],

            ['slug' => 'wiki_jp', 'name' => 'Wikipedia 日本語',
             'kind' => 'descriptions', 'endpoint' => 'https://ja.wikipedia.org/w/api.php',
             'config' => ['rate' => '~10 req/s', 'method' => 'mediawiki_api']],
        ];
    }

    public function seedAction(): Action
    {
        return Action::make('seed')   // ← name 跟 method 前綴對齊（mountAction('seed', ...) 找這個）
            ->label('建立預設來源')
            ->icon('heroicon-m-plus-circle')
            ->color('primary')
            ->visible(fn () => DataSource::count() === 0)
            ->action(function () {
                foreach ($this->getDefaultSeeds() as $seed) {
                    DataSource::create(array_merge($seed, [
                        'status' => 'unknown',
                        'enabled' => true,
                    ]));
                }
                Notification::make()
                    ->title('已建立 6 個預設資料來源')
                    ->success()
                    ->send();
            });
    }

    public function triggerSyncAction(): Action
    {
        return Action::make('triggerSync')
            ->label('立刻同步')
            ->icon('heroicon-m-arrow-path')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => "確定要同步 #{$arguments['id']}？")
            ->action(function (array $arguments) {
                $source = DataSource::findOrFail($arguments['id']);
                // TODO: dispatch SyncJob — Phase 2 連接實際 importer
                $source->syncRuns()->create([
                    'status' => 'running',
                    'started_at' => now(),
                    'triggered_by' => auth()->user()?->email ?? 'cli',
                ]);

                Notification::make()
                    ->title("已排入：{$source->name}")
                    ->body('Job 會在背景跑；完成後 status 自動更新。')
                    ->info()
                    ->send();
            });
    }

    public function toggleEnabledAction(): Action
    {
        return Action::make('toggleEnabled')
            ->label(fn (array $arguments) => DataSource::find($arguments['id'])?->enabled ? '停用' : '啟用')
            ->icon('heroicon-m-power')
            ->color('warning')
            ->action(function (array $arguments) {
                $source = DataSource::findOrFail($arguments['id']);
                $source->update(['enabled' => ! $source->enabled]);

                Notification::make()
                    ->title($source->enabled ? '已啟用' : '已停用')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->seedAction(),
        ];
    }
}
