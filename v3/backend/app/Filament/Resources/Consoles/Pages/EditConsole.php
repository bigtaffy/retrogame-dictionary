<?php

namespace App\Filament\Resources\Consoles\Pages;

use App\Filament\Resources\Consoles\ConsoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConsole extends EditRecord
{
    protected static string $resource = ConsoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
