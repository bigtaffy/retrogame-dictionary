<?php

namespace App\Filament\Resources\Games;

use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Games\Pages\EditGame;
use App\Filament\Resources\Games\Pages\ListGames;
use App\Filament\Resources\Games\RelationManagers\CheatsRelationManager;
use App\Filament\Resources\Games\RelationManagers\DescriptionsRelationManager;
use App\Filament\Resources\Games\RelationManagers\TitlesRelationManager;
use App\Filament\Resources\Games\Schemas\GameForm;
use App\Filament\Resources\Games\Tables\GamesTable;
use App\Models\Game;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static string|UnitEnum|null $navigationGroup = '目錄 Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = '遊戲';

    protected static ?string $pluralModelLabel = '遊戲';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return GameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GamesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TitlesRelationManager::class,
            DescriptionsRelationManager::class,
            CheatsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGames::route('/'),
            'create' => CreateGame::route('/create'),
            'edit' => EditGame::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Game>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['console', 'primaryTitle', 'coverImage']);
    }
}
