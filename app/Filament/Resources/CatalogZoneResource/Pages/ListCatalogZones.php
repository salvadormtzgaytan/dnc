<?php

namespace App\Filament\Resources\CatalogZoneResource\Pages;

use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CatalogZoneImporter;
use App\Filament\Resources\CatalogZoneResource;

class ListCatalogZones extends ListRecords
{
    protected static string $resource = CatalogZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
            ->importer(CatalogZoneImporter::class)
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->label('Importar zonas'),
        ];
    }
}
