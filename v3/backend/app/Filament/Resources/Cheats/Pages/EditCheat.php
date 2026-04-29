<?php

namespace App\Filament\Resources\Cheats\Pages;

use App\Filament\Resources\Cheats\CheatResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCheat extends EditRecord
{
    protected static string $resource = CheatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
