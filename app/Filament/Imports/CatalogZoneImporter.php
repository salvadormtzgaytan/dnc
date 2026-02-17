<?php

namespace App\Filament\Imports;

use App\Models\CatalogZone;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogZoneImporter extends Importer
{
    protected static ?string $model = CatalogZone::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('region_name')
                ->label('Región')
                ->requiredMapping()
                ->rules(['required']) //, 'exists:catalog_regions,name'
                ->fillRecordUsing(function ($record, $state) {
                    $regionId = \App\Models\CatalogRegion::where('name', $state)->value('id');
                    if (!$regionId) {
                        throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La región '{$state}' no existe.");
                    }
                    $record->region_id = $regionId;
                }),

            ImportColumn::make('name')
                ->label('Zona')
                ->requiredMapping()
                ->rules([
                    'required',
                    'max:255',
                    'unique:catalog_zones,name', // Valida que no esté duplicado
                ]),
        ];
    }

    public function resolveRecord(): ?CatalogZone
    {
        return new CatalogZone();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Tu importación se ha completado y ' . number_format($import->successful_rows) . ' ' . str('fila')->plural($import->successful_rows) . ' fueron importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('fila')->plural($failedRowsCount) . ' fallaron.';
        }

        return $body;
    }
}
