<?php

namespace App\Filament\Resources\DncUserOverrideResource\Pages;

use App\Filament\Resources\DncUserOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDncUserOverride extends EditRecord
{
    protected static string $resource = DncUserOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
