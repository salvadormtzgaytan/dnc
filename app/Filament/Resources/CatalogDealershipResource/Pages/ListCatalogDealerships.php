<?php

namespace App\Filament\Resources\CatalogDealershipResource\Pages;

use App\Filament\Resources\CatalogDealershipResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CatalogDealershipsImporter;

class ListCatalogDealerships extends ListRecords
{
    protected static string $resource = CatalogDealershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CatalogDealershipsImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->label('Importar Concesionarios'),
        ];
    }
}
