<?php

namespace App\Filament\Resources\CatalogDealershipResource\Pages;

use App\Filament\Resources\CatalogDealershipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCatalogDealership extends EditRecord
{
    protected static string $resource = CatalogDealershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
