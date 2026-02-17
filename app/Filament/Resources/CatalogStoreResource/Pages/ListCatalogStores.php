<?php

namespace App\Filament\Resources\CatalogStoreResource\Pages;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\CatalogStoreResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CatalogStoreImporter;

class ListCatalogStores extends ListRecords
{
    protected static string $resource = CatalogStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CatalogStoreImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->label('Importar Tiendas'),
        ];
    }
}
