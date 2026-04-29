<?php

namespace App\Filament\Resources\Consoles;

use App\Filament\Resources\Consoles\Pages\EditConsole;
use App\Filament\Resources\Consoles\Pages\ListConsoles;
use App\Filament\Resources\Consoles\Schemas\ConsoleForm;
use App\Filament\Resources\Consoles\Tables\ConsolesTable;
use App\Models\Console;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ConsoleResource extends Resource
{
    protected static ?string $model = Console::class;

    protected static string|UnitEnum|null $navigationGroup = '目錄 Catalog';
    // ↑ 這個 label 必須與 AdminPanelProvider::navigationGroups() 對應

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = '主機';

    protected static ?string $pluralModelLabel = '主機';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    public static function form(Schema $schema): Schema
    {
        return ConsoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsoles::route('/'),
            'edit' => EditConsole::route('/{record}/edit'),
        ];
    }
}
