<?php

namespace App\Filament\Resources\CatalogDivisionResource\Pages;

use App\Filament\Resources\CatalogDivisionResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Imports\CatalogDivisionsImporter;

class ListCatalogDivisions extends ListRecords
{
    protected static string $resource = CatalogDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->importer(CatalogDivisionsImporter::class)
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->label('Importar Divisiones'),
        ];
    }
}
