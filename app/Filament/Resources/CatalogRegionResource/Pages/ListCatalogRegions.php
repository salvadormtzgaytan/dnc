<?php

namespace App\Filament\Resources\CatalogRegionResource\Pages;

use App\Filament\Resources\CatalogRegionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ImportAction;
use App\Filament\Imports\CatalogRegionsImporter;

class ListCatalogRegions extends ListRecords
{
    protected static string $resource = CatalogRegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CatalogRegionsImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->label('Importar Regiones'),
        ];
    }
}
