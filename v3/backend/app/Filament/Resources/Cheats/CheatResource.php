<?php

namespace App\Filament\Resources\Cheats;

use App\Filament\Resources\Cheats\Pages\CreateCheat;
use App\Filament\Resources\Cheats\Pages\EditCheat;
use App\Filament\Resources\Cheats\Pages\ListCheats;
use App\Filament\Resources\Cheats\Schemas\CheatForm;
use App\Filament\Resources\Cheats\Tables\CheatsTable;
use App\Models\Cheat;
use BackedEnum;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CheatResource extends Resource
{
    protected static ?string $model = Cheat::class;

    protected static string|UnitEnum|null $navigationGroup = '目錄 Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = '秘技';

    protected static ?string $pluralModelLabel = '秘技';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'effect_zh';

    public static function form(Schema $schema): Schema
    {
        return CheatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheatsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCheats::route('/'),
            'create' => CreateCheat::route('/create'),
            'edit'   => EditCheat::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['game.console', 'contributor']);
    }
}
