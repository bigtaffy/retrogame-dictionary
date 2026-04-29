<?php

namespace App\Filament\Resources\Cheats\Schemas;

use App\Models\Cheat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CheatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基本')->columnSpanFull()->schema([
                Select::make('game_id')
                    ->label('遊戲')
                    ->relationship('game', 'slug')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($r) => "[{$r->console?->slug}] {$r->slug}"),

                Select::make('type')
                    ->label('種類')
                    ->options(Cheat::TYPES)
                    ->required()
                    ->native(false),

                Select::make('difficulty')
                    ->label('操作難度')
                    ->options(Cheat::DIFFICULTIES)
                    ->default('easy')
                    ->required(),

                Select::make('region')
                    ->label('適用地區')
                    ->options(['jp' => 'JP 日版', 'us' => 'US 美版', 'eu' => 'EU 歐版'])
                    ->placeholder('全 region 通用')
                    ->native(false),

                TextInput::make('rom_version')
                    ->label('ROM 版本')
                    ->placeholder('1.0 / Rev A')
                    ->maxLength(32),
            ]),

            Section::make('效果（多語）')->columnSpanFull()->schema([
                Tabs::make('effects')->columnSpanFull()->tabs([
                    Tab::make('中文')->schema([
                        TextInput::make('effect_zh')->label('一句話效果（中）')
                            ->placeholder('無敵 / 99 條命 / 選關')
                            ->maxLength(255)->columnSpanFull(),
                        Textarea::make('description_zh')->label('詳細說明（中）')
                            ->rows(4)->columnSpanFull(),
                    ]),
                    Tab::make('英文')->schema([
                        TextInput::make('effect_en')->label('Effect (EN)')
                            ->placeholder('Invincibility / 99 lives / Level select')
                            ->maxLength(255)->columnSpanFull(),
                        Textarea::make('description_en')->label('Description (EN)')
                            ->rows(4)->columnSpanFull(),
                    ]),
                    Tab::make('日文')->schema([
                        TextInput::make('effect_jp')->label('効果（日）')
                            ->placeholder('無敵 / 99 ライフ / ステージ選択')
                            ->maxLength(255)->columnSpanFull(),
                    ]),
                ]),
            ]),

            Section::make('秘技內容')->columnSpanFull()->schema([
                Textarea::make('code')
                    ->label('Code（按鈕 / 密碼 / hex）')
                    ->placeholder('↑↑↓↓←→←→BA  或  AAVELG  或  7E0019:09')
                    ->helperText('儲存時會自動產生 normalized 版本（去空格、方向統一成 ULDR）做 dedupe')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('trigger_at')
                    ->label('觸發位置')
                    ->placeholder('標題畫面 / Stage 1 暫停 / 輸入名字時')
                    ->maxLength(128)
                    ->columnSpanFull(),
            ]),

            Section::make('來源 + 審核')->columnSpanFull()->schema([
                TextInput::make('source')
                    ->label('來源')
                    ->placeholder('gamefaqs / tcrf / manual / community')
                    ->maxLength(64),
                TextInput::make('source_url')
                    ->label('來源 URL')
                    ->url()
                    ->maxLength(512),
                Toggle::make('verified')
                    ->label('已驗證')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
            ]),
        ])->columns(2);
    }
}
