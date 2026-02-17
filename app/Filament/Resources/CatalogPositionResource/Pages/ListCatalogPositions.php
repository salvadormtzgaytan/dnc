<?php

namespace App\Filament\Resources\CatalogPositionResource\Pages;

use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CatalogPositionImporter;
use App\Filament\Resources\CatalogPositionResource;

class ListCatalogPositions extends ListRecords
{
    protected static string $resource = CatalogPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CatalogPositionImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->label('Importar puestos'),
        ];
    }
}
