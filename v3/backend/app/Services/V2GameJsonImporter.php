<?php

namespace App\Services;

use App\Models\Console;
use App\Models\Description;
use App\Models\Game;
use App\Models\GameImage;
use App\Models\Genre;
use App\Models\Title;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 將 v2 風格之 games.json 單筆陣列匯入正規化表（所有主機共用）
 */
class V2GameJsonImporter
{
    /**
     * slug => 專案根目錄相對路徑（與 v2 / 前端 dataPath 一致）
     *
     * @return array<string, string>
     */
    public static function defaultPathsBySlug(): array
    {
        // 路徑對應 v2/data/*（2026-04 從 root/data 搬到 v2/data）
        return [
            'pce' => 'v2/data/games.json',
            'gb' => 'v2/data/gb/games.json',
            'gbc' => 'v2/data/gbc/games.json',
            'gba' => 'v2/data/gba/games.json',
            'fc' => 'v2/data/fc/games.json',
            'md' => 'v2/data/md/games.json',
        ];
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<string, int> slug => console_id
     */
    public static function consoleIdsBySlug(array $slugs): array
    {
        $rows = Console::query()
            ->whereIn('slug', $slugs)
            ->get(['id', 'slug']);

        $map = [];
        foreach ($rows as $c) {
            $map[$c->slug] = (int) $c->id;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function importOne(array $row, int $consoleId, string $consoleSlug): void
    {
        if (empty($row['id'])) {
            return;
        }

        $slug = Str::limit((string) $row['id'], 120, '');
        $rawRating = (string) ($row['rating'] ?? '');
        $dbRating = $this->normalizeRatingForDb($rawRating);

        $v2 = [
            'source_url' => (string) ($row['source_url'] ?? ''),
            'format' => (string) ($row['format'] ?? ''),
            'style' => (string) ($row['style'] ?? ''),
            'raw_release_date' => (string) ($row['release_date'] ?? ''),
            'genre_category' => (string) ($row['genre_category'] ?? ''),
            'aka' => (string) ($row['aka'] ?? ''),
            'rating_raw' => $rawRating,
            'region_category' => (string) ($row['region_category'] ?? ''),
            'region_flags' => (string) ($row['region_flags'] ?? ''),
            'year' => (string) ($row['year'] ?? ''),
        ];
        if (! empty($row['regions']) && is_array($row['regions'])) {
            $v2['regions'] = array_values(array_map('strval', $row['regions']));
        }
        if (isset($row['developer']) && is_string($row['developer'])) {
            $v2['developer'] = $row['developer'];
        }

        $sourceOrigin = $consoleSlug === 'pce' ? 'pcengine.co.uk' : 'v2_json';

        $game = Game::query()->updateOrCreate(
            ['console_id' => $consoleId, 'slug' => $slug],
            [
                'no_intro_name' => isset($row['no_intro_name']) ? Str::limit((string) $row['no_intro_name'], 255) : null,
                'letter' => isset($row['letter']) ? Str::limit((string) $row['letter'], 4) : null,
                'maker' => isset($row['maker']) ? Str::limit((string) $row['maker'], 128) : null,
                'publisher' => $this->publisherFromRow($row),
                'release_year' => $this->parseYear($row['year'] ?? null),
                'format_category' => isset($row['format_category']) ? Str::limit((string) $row['format_category'], 32) : null,
                'rating' => $dbRating,
                'external_links' => ['v2' => $v2],
                'source_origin' => $sourceOrigin,
                'primary_title_id' => null,
                'cover_image_id' => null,
            ],
        );

        $game->update(['primary_title_id' => null, 'cover_image_id' => null]);

        $game->titles()->delete();
        $game->descriptions()->delete();
        $game->images()->delete();
        $game->videos()->delete();
        $game->genres()->detach();

        $this->insertTitles($game, $row, $consoleSlug);
        $this->insertDescriptions($game, $row, $consoleSlug);
        $this->insertImages($game, $row, $consoleSlug);
        $this->insertVideos($game, $row, $consoleSlug);
        $this->attachGenre($game, $row);

        $primaryId = $this->choosePrimaryTitleId($game, $row);
        $game->update([
            'primary_title_id' => $primaryId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function publisherFromRow(array $row): ?string
    {
        $p = (string) ($row['publisher'] ?? '');
        if ($p === '') {
            return null;
        }

        return Str::limit($p, 128);
    }

    private function parseYear(mixed $y): ?int
    {
        if ($y === null || $y === '') {
            return null;
        }
        $s = is_string($y) || is_numeric($y) ? (string) $y : '';
        if ($s === '') {
            return null;
        }
        if (preg_match('/(\d{4})/', $s, $m)) {
            $n = (int) $m[1];

            return $n > 1900 && $n < 2100 ? $n : null;
        }

        return null;
    }

    private function normalizeRatingForDb(string $raw): string
    {
        $r = strtolower(preg_replace('/\s+/', '', $raw));
        if ($r === '') {
            return 'unrated';
        }
        if (str_contains($r, 'buy')) {
            return 'buyit';
        }
        if (str_contains($r, 'try')) {
            return 'tryit';
        }
        if (str_contains($r, 'avoid')) {
            return 'avoid';
        }
        if (in_array($r, ['buyit', 'tryit', 'avoid', 'unrated'], true)) {
            return $r;
        }

        return 'unrated';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function insertTitles(Game $game, array $row, string $consoleSlug): void
    {
        $src = 'v2_'.$consoleSlug;
        $pairs = [
            'en' => (string) ($row['title_en'] ?? ''),
            'jp' => (string) ($row['title_jp'] ?? ''),
            'zh' => (string) ($row['title_zh'] ?? ''),
        ];
        foreach ($pairs as $lang => $text) {
            if ($text === '') {
                continue;
            }
            Title::query()->create([
                'game_id' => $game->id,
                'language' => $lang,
                'text' => Str::limit($text, 255),
                'is_aka' => false,
                'source' => $src,
            ]);
        }
        if (! empty($row['aka']) && is_string($row['aka']) && $row['aka'] !== '') {
            Title::query()->create([
                'game_id' => $game->id,
                'language' => 'en',
                'text' => Str::limit($row['aka'], 255),
                'is_aka' => true,
                'source' => $src,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function insertDescriptions(Game $game, array $row, string $consoleSlug): void
    {
        $src = 'v2_'.$consoleSlug;
        $map = [
            ['overview', 'en', (string) ($row['overview_en'] ?? ''), $src],
            ['overview', 'zh', (string) ($row['overview_zh'] ?? ''), $src],
            ['comment', 'en', (string) ($row['comment_en'] ?? ''), $src],
            ['comment', 'zh', (string) ($row['comment_zh'] ?? ''), $src],
        ];
        foreach ($map as [$kind, $lang, $text, $s]) {
            if ($text === '') {
                continue;
            }
            Description::query()->create([
                'game_id' => $game->id,
                'kind' => $kind,
                'language' => $lang,
                'text' => $text,
                'source' => $s,
                'source_url' => null,
                'is_primary' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function insertImages(Game $game, array $row, string $consoleSlug): void
    {
        $snapSource = $consoleSlug === 'pce' ? 'pcengine.co.uk' : 'libretro';

        if (! empty($row['cover']) && is_string($row['cover'])) {
            $img = GameImage::query()->create([
                'game_id' => $game->id,
                'kind' => 'cover',
                'url' => $row['cover'],
                'source' => $consoleSlug === 'pce' ? 'libretro' : 'libretro',
                'sort_order' => 0,
            ]);
            $game->update(['cover_image_id' => $img->id]);
        }
        if (! empty($row['screenshots']) && is_array($row['screenshots'])) {
            $i = 0;
            foreach ($row['screenshots'] as $s) {
                $url = $this->normalizeScreenshotUrl($s);
                if ($url === null || $url === '') {
                    continue;
                }
                GameImage::query()->create([
                    'game_id' => $game->id,
                    'kind' => 'snap',
                    'url' => $url,
                    'source' => $snapSource,
                    'sort_order' => $i,
                ]);
                $i++;
            }
        }
    }

    private function normalizeScreenshotUrl(mixed $s): ?string
    {
        if (is_string($s) && $s !== '') {
            return $s;
        }
        if (is_array($s) && isset($s['url']) && is_string($s['url']) && $s['url'] !== '') {
            return $s['url'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function insertVideos(Game $game, array $row, string $consoleSlug): void
    {
        if (empty($row['youtube']) || ! is_array($row['youtube'])) {
            return;
        }
        $src = 'v2_'.$consoleSlug;
        $i = 0;
        foreach ($row['youtube'] as $id) {
            if (! is_string($id) && ! is_int($id)) {
                continue;
            }
            $sid = (string) $id;
            if ($sid === '') {
                continue;
            }
            Video::query()->create([
                'game_id' => $game->id,
                'provider' => 'youtube',
                'external_id' => Str::limit($sid, 64),
                'source' => $src,
                'sort_order' => $i,
            ]);
            $i++;
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function attachGenre(Game $game, array $row): void
    {
        $gcat = (string) ($row['genre_category'] ?? '');
        if ($gcat === '') {
            return;
        }
        $slug = 'g-'.substr(md5($gcat), 0, 12);
        $genre = Genre::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name_en' => Str::limit($gcat, 64),
                'name_zh' => Str::limit($gcat, 64),
                'parent_id' => null,
                'sort_order' => 0,
            ],
        );
        $game->genres()->attach($genre->id, ['is_primary' => true]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function choosePrimaryTitleId(Game $game, array $row): ?int
    {
        $tZh = (string) ($row['title_zh'] ?? '');
        if ($tZh !== '') {
            $t = $game->titles()->where('language', 'zh')->where('is_aka', false)->first();
        } else {
            $t = $game->titles()->where('language', 'en')->where('is_aka', false)->first();
        }
        if (! $t) {
            $t = $game->titles()->where('is_aka', false)->orderBy('id')->first();
        }

        return $t?->id;
    }

    /**
     * 刪除單一主機底下所有遊戲及關聯（titles, descriptions, images, videos, game_genres）
     */
    public function truncateConsole(int $consoleId): void
    {
        DB::transaction(function () use ($consoleId) {
            $ids = Game::query()->where('console_id', $consoleId)->pluck('id');
            if ($ids->isEmpty()) {
                return;
            }
            DB::table('game_genres')->whereIn('game_id', $ids)->delete();
            DB::table('game_regions')->whereIn('game_id', $ids)->delete();
            Video::query()->whereIn('game_id', $ids)->delete();
            GameImage::query()->whereIn('game_id', $ids)->delete();
            Description::query()->whereIn('game_id', $ids)->delete();
            Title::query()->whereIn('game_id', $ids)->delete();
            Game::query()->where('console_id', $consoleId)->delete();
        });
    }

    public function refreshGameCounts(): void
    {
        foreach (Console::query()->get() as $c) {
            $n = Game::query()->where('console_id', $c->id)->count();
            $c->update(['game_count_cached' => $n]);
        }
    }
}
