<?php

namespace App\Filament\Resources\Games\Tables;

use App\Models\Game;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GamesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 封面縮圖 — coverImage.url 是絕對 URL（libretro / famicom.tw / wiki）
                ImageColumn::make('coverImage.url')
                    ->label('封面')
                    ->height(56)
                    ->extraImgAttributes(['loading' => 'lazy', 'class' => 'rounded-md object-cover'])
                    ->defaultImageUrl(fn () => 'data:image/svg+xml;base64,'.base64_encode(
                        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="56" viewBox="0 0 40 56"><rect width="40" height="56" fill="#221b3d"/><text x="20" y="32" font-size="10" fill="#6b5b9a" text-anchor="middle">無</text></svg>'
                    )),

                TextColumn::make('console.name_zh')
                    ->label('主機')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->limit(40)
                    ->tooltip(fn (Game $r): string => (string) $r->slug),

                TextColumn::make('primaryTitle.text')
                    ->label('標題（主）')
                    // Filament 4 closure resolver 認 $search（命名 DI），不認 $s → 之前 500
                    ->searchable(query: function (Builder $query, string $search): void {
                        $query->whereHas('primaryTitle', fn (Builder $t) => $t->where('text', 'like', '%'.$search.'%'));
                    })
                    ->default('—')
                    ->limit(32),

                TextColumn::make('letter')
                    ->label('字')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('maker')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('format_category')
                    ->label('格式')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rating')
                    ->label('評分')
                    ->badge()
                    ->sortable(),

                TextColumn::make('view_count')
                    ->label('瀏覽')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('更新')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 'all'])
            ->filters([
                SelectFilter::make('console_id')
                    ->label('主機')
                    ->relationship('console', 'name_zh')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('rating')
                    ->options([
                        'buyit' => '必買',
                        'tryit' => '可試',
                        'avoid' => '勸退',
                        'unrated' => '未評',
                    ]),

                // 給 CoverageMatrixPage click-through 用。每個 filter 是 ternary：
                //   true  = 缺
                //   false = 已填
                //   null  = 不過濾
                TernaryFilter::make('missing_year')
                    ->label('缺發行年')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('release_year'),
                        false: fn (Builder $q) => $q->whereNotNull('release_year'),
                    ),

                TernaryFilter::make('missing_maker')
                    ->label('缺廠商')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->where(fn ($q) => $q->whereNull('maker')->orWhere('maker', '')),
                        false: fn (Builder $q) => $q->whereNotNull('maker')->where('maker', '!=', ''),
                    ),

                TernaryFilter::make('missing_no_intro')
                    ->label('缺 no_intro')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('no_intro_name'),
                        false: fn (Builder $q) => $q->whereNotNull('no_intro_name'),
                    ),

                TernaryFilter::make('missing_cover')
                    ->label('缺封面')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('cover_image_id'),
                        false: fn (Builder $q) => $q->whereNotNull('cover_image_id'),
                    ),

                // descriptions 表結構是 (kind, language, text)，不是各語言一欄。
                TernaryFilter::make('missing_overview_zh')
                    ->label('缺中文簡介')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('descriptions', fn (Builder $d) => $d->where('kind', 'overview')->where('language', 'zh')->whereNotNull('text')->where('text', '!=', '')),
                        false: fn (Builder $q) => $q->whereHas('descriptions', fn (Builder $d) => $d->where('kind', 'overview')->where('language', 'zh')->whereNotNull('text')->where('text', '!=', '')),
                    ),

                TernaryFilter::make('missing_overview_en')
                    ->label('缺英文簡介')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('descriptions', fn (Builder $d) => $d->where('kind', 'overview')->where('language', 'en')->whereNotNull('text')->where('text', '!=', '')),
                        false: fn (Builder $q) => $q->whereHas('descriptions', fn (Builder $d) => $d->where('kind', 'overview')->where('language', 'en')->whereNotNull('text')->where('text', '!=', '')),
                    ),

                // titles.language 是 'zh' / 'jp' / 'en'（char(3)），不是 BCP-47 codes。
                TernaryFilter::make('missing_title_zh')
                    ->label('缺中文標題')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('titles', fn (Builder $t) => $t->where('language', 'zh')->where('is_aka', false)),
                        false: fn (Builder $q) => $q->whereHas('titles', fn (Builder $t) => $t->where('language', 'zh')->where('is_aka', false)),
                    ),

                TernaryFilter::make('missing_title_jp')
                    ->label('缺日文標題')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('titles', fn (Builder $t) => $t->where('language', 'jp')->where('is_aka', false)),
                        false: fn (Builder $q) => $q->whereHas('titles', fn (Builder $t) => $t->where('language', 'jp')->where('is_aka', false)),
                    ),

                // images.kind enum 用 'snap'（沿用 libretro 命名）做截圖
                TernaryFilter::make('missing_screenshot')
                    ->label('缺截圖')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('images', fn (Builder $i) => $i->where('kind', 'snap')),
                        false: fn (Builder $q) => $q->whereHas('images', fn (Builder $i) => $i->where('kind', 'snap')),
                    ),

                TernaryFilter::make('missing_video')
                    ->label('缺影片')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('videos'),
                        false: fn (Builder $q) => $q->whereHas('videos'),
                    ),

                TernaryFilter::make('missing_genre')
                    ->label('缺類型')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->whereDoesntHave('genres'),
                        false: fn (Builder $q) => $q->whereHas('genres'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
