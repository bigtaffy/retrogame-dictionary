<?php

namespace App\Filament\Resources\Games\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 介紹 / 短評 — 直接 inline 編輯。這 4 個欄位不在 games 表本身，
                // 在 EditGame::mutateFormDataBeforeFill / handleRecordUpdate 雙向同步到 descriptions。
                Section::make('遊戲介紹 / 短評')
                    ->description('中英文 overview / comment 直接編輯。儲存時會寫回 descriptions 表（is_primary=true）。')
                    ->columnSpanFull()
                    ->schema([
                        Tabs::make('descTabs')
                            ->columnSpanFull()
                            ->tabs([
                                Tab::make('中文簡介 overview')
                                    ->schema([
                                        Textarea::make('overview_zh')
                                            ->label('overview_zh')
                                            ->rows(8)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('英文簡介 overview')
                                    ->schema([
                                        Textarea::make('overview_en')
                                            ->label('overview_en')
                                            ->rows(8)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('中文短評 comment')
                                    ->schema([
                                        Textarea::make('comment_zh')
                                            ->label('comment_zh')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('英文短評 comment')
                                    ->schema([
                                        Textarea::make('comment_en')
                                            ->label('comment_en')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                // 圖片管理 — 封面 URL 直接編輯 + 截圖 Repeater
                // 在 EditGame::mutateFormDataBeforeFill / handleRecordUpdate 同步到 images 表
                Section::make('封面 / 截圖')
                    ->description('cover URL 直接貼。截圖可加 / 刪 / 拖排序，按 Save 才生效。')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('cover_url')
                            ->label('封面 URL')
                            ->url()
                            ->prefix('🖼️')
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->helperText(fn ($state) => $state
                                ? new HtmlString(
                                    '<div style="display:flex;gap:.75rem;align-items:flex-start;margin-top:.25rem">'
                                    .'<img src="'.e($state).'" alt="cover" style="max-height:160px;max-width:120px;border-radius:8px;border:1px solid rgba(255,62,200,0.2)" loading="lazy" onerror="this.style.opacity=0.3" />'
                                    .'<span style="font-size:.75rem;opacity:.7">絕對 URL（libretro / GitHub raw / wiki / R2 自架）。留空 = 移除封面。</span>'
                                    .'</div>'
                                )
                                : '尚無封面（貼絕對 URL）'),

                        Repeater::make('screenshots')
                            ->label('截圖（kind=snap）')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->columnSpan(4),
                                TextInput::make('source')
                                    ->label('來源')
                                    ->maxLength(64)
                                    ->placeholder('libretro / wiki / 手動')
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->itemLabel(fn (array $state): ?string =>
                                isset($state['url']) ? basename(parse_url($state['url'], PHP_URL_PATH) ?? '') : null
                            )
                            ->addActionLabel('+ 加截圖')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->defaultItems(0),

                        Repeater::make('extra_images')
                            ->label('其他圖（marquee / wheel / title_screen / box_back …）')
                            ->columnSpanFull()
                            ->schema([
                                Select::make('kind')
                                    ->label('類型')
                                    ->options([
                                        'title_screen' => 'title_screen 標題畫面',
                                        'marquee' => 'marquee',
                                        'wheel' => 'wheel',
                                        'box_back' => 'box_back 背面',
                                        'cart' => 'cart 卡帶',
                                        'manual' => 'manual 說明書',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->columnSpan(3),
                                TextInput::make('source')
                                    ->label('來源')
                                    ->maxLength(64)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->addActionLabel('+ 加其他圖')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0),
                    ]),

                Select::make('console_id')
                    ->label('主機')
                    ->relationship('console', 'name_zh')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('slug')
                    ->label('ID（v2 之 id / URL slug）')
                    ->required()
                    ->maxLength(120)
                    ->helperText('與前站 / API 的 legacy id 相同，一般勿隨意更改'),

                TextInput::make('letter')
                    ->label('字母列')
                    ->maxLength(4),

                TextInput::make('maker')
                    ->label('開發/廠商')
                    ->maxLength(128),

                TextInput::make('publisher')
                    ->label('發行商')
                    ->maxLength(128),

                TextInput::make('format_category')
                    ->label('格式分類')
                    ->maxLength(32),

                Select::make('rating')
                    ->label('評分')
                    ->options([
                        'buyit' => '必買 buyit',
                        'tryit' => '可試 tryit',
                        'avoid' => '勸退 avoid',
                        'unrated' => '未評 unrated',
                    ])
                    ->default('unrated')
                    ->required(),

                TextInput::make('release_year')
                    ->label('年份')
                    ->numeric()
                    ->minValue(1970)
                    ->maxValue(2100),

                TextInput::make('source_origin')
                    ->label('資料來源代碼')
                    ->maxLength(64),

                TextInput::make('view_count')
                    ->label('瀏覽次數')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Textarea::make('external_links')
                    ->label('external_links（JSON，含 v2 備用欄位）')
                    ->rows(12)
                    ->helperText('可貼上 JSON 物件。留空可清空。')
                    ->formatStateUsing(function ($state) {
                        if ($state === null || $state === []) {
                            return '';
                        }

                        return is_array($state)
                            ? (json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '')
                            : (string) $state;
                    })
                    ->dehydrateStateUsing(function (?string $state) {
                        if ($state === null || trim($state) === '') {
                            return null;
                        }
                        $decoded = json_decode($state, true);

                        return is_array($decoded) ? $decoded : null;
                    }),
            ]);
    }
}
