<?php

namespace App\Filament\Resources\CatalogZoneResource\Pages;

use App\Filament\Resources\CatalogZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogZone extends EditRecord
{
    protected static string $resource = CatalogZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
