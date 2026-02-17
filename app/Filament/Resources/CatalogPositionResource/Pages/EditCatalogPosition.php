<?php

namespace App\Filament\Resources\CatalogPositionResource\Pages;

use App\Filament\Resources\CatalogPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogPosition extends EditRecord
{
    protected static string $resource = CatalogPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
