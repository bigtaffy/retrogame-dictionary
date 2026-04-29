<?php

namespace App\Filament\Resources\Games\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TitlesRelationManager extends RelationManager
{
    protected static string $relationship = 'titles';

    protected static ?string $title = '多語標題 Titles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('language')
                    ->label('語言')
                    ->options(['en' => '英文 en', 'jp' => '日文 jp', 'zh' => '中文 zh'])
                    ->required(),
                TextInput::make('text')
                    ->label('標題')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_aka')
                    ->label('別名 / AKA')
                    ->default(false),
                TextInput::make('source')
                    ->label('來源')
                    ->maxLength(64)
                    ->default('admin'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('language')
                    ->label('語言')
                    ->badge(),
                TextColumn::make('text')
                    ->label('標題')
                    ->searchable()
                    ->limit(40),
                IconColumn::make('is_aka')
                    ->label('AKA')
                    ->boolean(),
                TextColumn::make('source')
                    ->label('來源')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
