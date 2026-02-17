<?php

namespace App\Filament\Resources\CatalogStoreResource\Pages;

use App\Filament\Resources\CatalogStoreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogStore extends EditRecord
{
    protected static string $resource = CatalogStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
