<?php

namespace App\Console\Commands;

use App\Services\V2GameJsonImporter;
use Illuminate\Console\Command;

class ImportV2Json extends Command
{
    protected $signature = 'rgd:import-v2
        {--console=* : 主機 slug：pce, gb, gbc, gba, fc, md；可重複；含 all 則匯入全部}
        {--path= : 單一 JSON 路徑（僅在只匯入一臺主機時使用，覆寫預設 data/... 路徑）}
        {--truncate : 匯入前清空該主機既有遊戲}';

    protected $description = '從倉庫 v2 之 data/**/games.json 匯入 pce/gb/gbc/gba/fc/md（`--console=all`）';

    public function handle(V2GameJsonImporter $importer): int
    {
        $opt = $this->option('console');
        if ($opt === [] || $opt === null) {
            $this->error('請加 --console=pce 或 --console=all 等。');

            return self::FAILURE;
        }

        $paths = V2GameJsonImporter::defaultPathsBySlug();
        $allSlugs = array_keys($paths);

        if (in_array('all', $opt, true)) {
            $slugs = $allSlugs;
        } else {
            $slugs = [];
            foreach ($opt as $o) {
                if (! in_array($o, $allSlugs, true)) {
                    $this->error('未知主機 slug：'.$o.'（允許：'.implode(', ', $allSlugs).', all）');

                    return self::FAILURE;
                }
                $slugs[] = $o;
            }
            $slugs = array_values(array_unique($slugs));
        }

        if (count($slugs) > 1 && $this->option('path')) {
            $this->error('指定多臺主機時不可使用 --path。');

            return self::FAILURE;
        }

        $idBySlug = V2GameJsonImporter::consoleIdsBySlug($slugs);
        foreach ($slugs as $s) {
            if (! isset($idBySlug[$s])) {
                $this->error("資料庫沒有 consoles.slug={$s}，請先 php artisan migrate。");

                return self::FAILURE;
            }
        }

        $root = dirname(base_path(), 2);
        $override = $this->option('path');
        foreach ($slugs as $slug) {
            if (count($slugs) === 1 && is_string($override) && $override !== '') {
                $path = $override;
                if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Z]:\\\\/', $path)) {
                    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
                }
            } else {
                $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $paths[$slug]);
            }

            if (! is_readable($path)) {
                $this->error('讀不到檔案：'.$path);

                return self::FAILURE;
            }

            $consoleId = $idBySlug[$slug];
            if ($this->option('truncate')) {
                $this->warn("正在清空主機 {$slug} (id={$consoleId}) 的遊戲…");
                $importer->truncateConsole($consoleId);
            }

            $raw = file_get_contents($path);
            if ($raw === false) {
                $this->error('無法讀取：'.$path);

                return self::FAILURE;
            }
            $arr = json_decode($raw, true);
            if (! is_array($arr)) {
                $this->error('JSON 解析失敗：'.$path);

                return self::FAILURE;
            }

            $this->info("匯入 {$slug}：".count($arr).' 筆 ← '.$path);
            $bar = $this->output->createProgressBar(count($arr));
            $bar->start();

            foreach ($arr as $row) {
                if (! is_array($row) || empty($row['id'])) {
                    $bar->advance();
                    continue;
                }
                try {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($importer, $row, $consoleId, $slug) {
                        $importer->importOne($row, $consoleId, $slug);
                    });
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->error('匯入失敗 id='.($row['id'] ?? '?').' '.$e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $importer->refreshGameCounts();
        $this->info('完成。已更新各主機 game_count_cached。');

        return self::SUCCESS;
    }
}
