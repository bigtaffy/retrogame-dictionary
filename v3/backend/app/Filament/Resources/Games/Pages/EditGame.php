<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\GameResource;
use App\Models\Description;
use App\Models\Game;
use App\Models\GameImage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * 把 hasMany 關聯的資料拉進 form：
     *   - descriptions  → overview_zh / overview_en / comment_zh / comment_en
     *   - coverImage    → cover_url
     *   - images(snap)  → screenshots Repeater
     *   - images(其他)  → extra_images Repeater
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Game|null $g */
        $g = $this->getRecord();
        if (! $g) {
            return $data;
        }

        // === 簡介 / 短評 ===
        $descs = $g->descriptions()
            ->whereIn('kind', ['overview', 'comment'])
            ->whereIn('language', ['zh', 'en'])
            ->where('is_primary', true)
            ->get(['kind', 'language', 'text']);

        foreach ([['overview', 'zh'], ['overview', 'en'], ['comment', 'zh'], ['comment', 'en']] as [$kind, $lang]) {
            $row = $descs->first(fn (Description $d) => $d->kind === $kind && $d->language === $lang);
            $data["{$kind}_{$lang}"] = $row?->text ?? '';
        }

        // === 封面 ===
        $data['cover_url'] = $g->coverImage?->url ?? '';

        // === 截圖（kind=snap）===
        $data['screenshots'] = $g->images()
            ->where('kind', 'snap')
            ->orderBy('sort_order')
            ->get(['url', 'source'])
            ->map(fn (GameImage $i) => ['url' => $i->url, 'source' => $i->source ?? ''])
            ->all();

        // === 其他圖（非 cover、非 snap）===
        $data['extra_images'] = $g->images()
            ->whereNotIn('kind', ['cover', 'snap'])
            ->orderBy('kind')
            ->orderBy('sort_order')
            ->get(['kind', 'url', 'source'])
            ->map(fn (GameImage $i) => ['kind' => $i->kind, 'url' => $i->url, 'source' => $i->source ?? ''])
            ->all();

        return $data;
    }

    /**
     * 儲存時把 4 個 description textarea + 圖片 Repeaters 寫回 hasMany 表。
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // === 拆出非 games 表的欄位 ===
        $descMap = [
            ['overview', 'zh', $data['overview_zh'] ?? null],
            ['overview', 'en', $data['overview_en'] ?? null],
            ['comment',  'zh', $data['comment_zh']  ?? null],
            ['comment',  'en', $data['comment_en']  ?? null],
        ];
        $coverUrl = trim((string) ($data['cover_url'] ?? ''));
        $screenshots = $data['screenshots'] ?? [];
        $extraImages = $data['extra_images'] ?? [];

        unset(
            $data['overview_zh'], $data['overview_en'],
            $data['comment_zh'], $data['comment_en'],
            $data['cover_url'], $data['screenshots'], $data['extra_images']
        );

        $record->update($data);

        /** @var Game $record */

        // === 簡介 / 短評（空字串 = 保留現狀，不刪）===
        foreach ($descMap as [$kind, $lang, $text]) {
            if ($text === null || trim($text) === '') {
                continue;
            }
            $record->descriptions()->updateOrCreate(
                ['kind' => $kind, 'language' => $lang, 'is_primary' => true],
                ['text' => $text, 'source' => 'admin'],
            );
        }

        // === 封面 ===
        if ($coverUrl === '') {
            // 空 = 移除封面（清掉 cover_image_id，但保留 images 表中的 row 以免破壞歷史）
            if ($record->cover_image_id) {
                $record->update(['cover_image_id' => null]);
            }
        } else {
            $cover = $record->coverImage;
            if ($cover) {
                $cover->update(['url' => $coverUrl, 'source' => $cover->source ?? 'admin']);
            } else {
                $cover = $record->images()->create([
                    'kind' => 'cover',
                    'url' => $coverUrl,
                    'source' => 'admin',
                    'sort_order' => 0,
                ]);
                $record->update(['cover_image_id' => $cover->id]);
            }
        }

        // === 截圖（full replace：刪舊 + 全部重建，保留排序）===
        $record->images()->where('kind', 'snap')->delete();
        $i = 0;
        foreach ($screenshots as $row) {
            if (empty($row['url'])) {
                continue;
            }
            $record->images()->create([
                'kind' => 'snap',
                'url' => $row['url'],
                'source' => $row['source'] ?? 'admin',
                'sort_order' => $i++,
            ]);
        }

        // === 其他圖（full replace by non-cover-non-snap kinds）===
        $record->images()->whereNotIn('kind', ['cover', 'snap'])->delete();
        $i = 0;
        foreach ($extraImages as $row) {
            if (empty($row['url']) || empty($row['kind'])) {
                continue;
            }
            $record->images()->create([
                'kind' => $row['kind'],
                'url' => $row['url'],
                'source' => $row['source'] ?? 'admin',
                'sort_order' => $i++,
            ]);
        }

        return $record;
    }
}
