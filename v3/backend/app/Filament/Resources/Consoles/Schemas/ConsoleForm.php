<?php

namespace App\Filament\Resources\Consoles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConsoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label('Slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('內建主機，勿改'),
                TextInput::make('name_en')
                    ->label('英文名')
                    ->required()
                    ->maxLength(64),
                TextInput::make('name_zh')
                    ->label('中文名')
                    ->required()
                    ->maxLength(64),
                TextInput::make('name_jp')
                    ->label('日文名')
                    ->required()
                    ->maxLength(64),
                TextInput::make('manufacturer')
                    ->label('廠牌')
                    ->maxLength(64),
                TextInput::make('release_year')
                    ->label('代表年份')
                    ->numeric(),
                TextInput::make('icon_url')
                    ->label('圖示 URL')
                    ->url()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('排序（小在前）')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('game_count_cached')
                    ->label('遊戲筆數（快取）')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('匯入 rgd:import-v2 後會自動重算，一般不需手改'),
            ]);
    }
}
