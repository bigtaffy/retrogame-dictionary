<?php

namespace App\Filament\Resources\Consoles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name_zh')
                    ->label('名稱（中）')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('名稱（英）')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manufacturer')
                    ->label('廠牌')
                    ->toggleable(),
                TextColumn::make('game_count_cached')
                    ->label('遊戲數')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
