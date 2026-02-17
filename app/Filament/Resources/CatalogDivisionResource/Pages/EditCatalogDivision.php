<?php

namespace App\Filament\Resources\CatalogDivisionResource\Pages;

use App\Filament\Resources\CatalogDivisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogDivision extends EditRecord
{
    protected static string $resource = CatalogDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
