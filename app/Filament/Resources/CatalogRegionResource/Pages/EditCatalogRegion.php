<?php

namespace App\Filament\Resources\CatalogRegionResource\Pages;

use App\Filament\Resources\CatalogRegionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogRegion extends EditRecord
{
    protected static string $resource = CatalogRegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
