<?php

namespace App\Filament\Resources\Games\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DescriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'descriptions';

    protected static ?string $title = '簡介 / 短評 Descriptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label('類型')
                    ->options([
                        'overview' => '概覽 overview',
                        'comment' => '短評 comment',
                    ])
                    ->required(),
                Select::make('language')
                    ->label('語言')
                    ->options(['en' => '英文 en', 'zh' => '中文 zh'])
                    ->required(),
                Textarea::make('text')
                    ->label('內文')
                    ->required()
                    ->rows(6),
                TextInput::make('source')
                    ->label('來源')
                    ->maxLength(64),
                TextInput::make('source_url')
                    ->label('來源 URL')
                    ->url()
                    ->maxLength(500),
                Toggle::make('is_primary')
                    ->label('主用')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('kind')
                    ->label('類型')
                    ->badge(),
                TextColumn::make('language')
                    ->label('語言')
                    ->badge(),
                TextColumn::make('text')
                    ->label('內文')
                    ->searchable()
                    ->limit(48)
                    ->wrap(),
                IconColumn::make('is_primary')
                    ->label('主用')
                    ->boolean(),
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
