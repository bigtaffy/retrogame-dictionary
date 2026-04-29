<?php

namespace App\Filament\Resources\Cheats\Tables;

use App\Models\Cheat;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('game.console.slug')
                    ->label('主機')
                    ->badge()
                    ->sortable(),

                TextColumn::make('game.slug')
                    ->label('遊戲')
                    ->searchable()
                    ->limit(28)
                    ->tooltip(fn (Cheat $r): string => (string) ($r->game?->slug ?? '')),

                TextColumn::make('type')
                    ->label('種類')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Cheat::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'button_sequence' => 'info',
                        'password' => 'warning',
                        'game_genie', 'pro_action_replay', 'memory_patch' => 'danger',
                        'glitch' => 'gray',
                        'easter_egg' => 'success',
                        'unlock' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('effect_zh')
                    ->label('效果（中）')
                    ->searchable()
                    ->limit(40)
                    ->wrap()
                    ->placeholder('—'),

                TextColumn::make('effect_en')
                    ->label('Effect (EN)')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('code')
                    ->label('Code')
                    ->limit(28)
                    ->copyable()
                    ->fontFamily('mono')
                    ->tooltip(fn (Cheat $r): string => (string) $r->code),

                TextColumn::make('difficulty')
                    ->label('難度')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Cheat::DIFFICULTIES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('region')
                    ->label('地區')
                    ->badge()
                    ->placeholder('全')
                    ->toggleable(),

                IconColumn::make('verified')
                    ->label('驗證')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('source')
                    ->label('來源')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('contributor.name')
                    ->label('提交者')
                    ->placeholder('—')
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
                SelectFilter::make('type')
                    ->label('種類')
                    ->options(Cheat::TYPES),

                SelectFilter::make('difficulty')
                    ->label('難度')
                    ->options(Cheat::DIFFICULTIES),

                SelectFilter::make('console_id')
                    ->label('主機')
                    ->relationship('game.console', 'name_zh')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('verified')
                    ->label('驗證狀態')
                    ->placeholder('全部')
                    ->trueLabel('已驗證')
                    ->falseLabel('待驗證'),

                TernaryFilter::make('missing_zh')
                    ->label('缺中文效果')
                    ->placeholder('全部')
                    ->trueLabel('缺')->falseLabel('已填')
                    ->queries(
                        true: fn (Builder $q) => $q->where(fn ($q) => $q->whereNull('effect_zh')->orWhere('effect_zh', '')),
                        false: fn (Builder $q) => $q->whereNotNull('effect_zh')->where('effect_zh', '!=', ''),
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
