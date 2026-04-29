<?php

namespace App\Filament\Resources\Cheats\Pages;

use App\Filament\Resources\Cheats\CheatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheats extends ListRecords
{
    protected static string $resource = CheatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
