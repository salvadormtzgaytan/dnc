<?php

namespace App\Filament\Imports;

use App\Models\CatalogDealership;
use App\Models\CatalogZone;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogDealershipsImporter extends Importer
{
    protected static ?string $model = CatalogDealership::class;

    /**
     * Define las columnas que serán importadas para los concesionarios.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('zone_name')
                ->label('Zona')
                ->requiredMapping()
                ->rules(['required']) //, 'exists:catalog_zones,name'
                ->fillRecordUsing(function ($record, $state) {
                    $zoneId = CatalogZone::where('name', $state)->value('id');
                    if (!$zoneId) {
                        throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La zona '{$state}' no existe.");
                    }
                    $record->zone_id = $zoneId;
                }),

            ImportColumn::make('name')
                ->label('Concesionario')
                ->requiredMapping()
                ->rules([
                    'required',
                    'max:255',
                    'unique:catalog_dealerships,name',
                ]),
        ];
    }

    /**
     * Crea una nueva instancia de CatalogDealership para cada fila importada.
     *
     * @return CatalogDealership|null
     */
    public function resolveRecord(): ?CatalogDealership
    {
        return new CatalogDealership();
    }

    /**
     * Mensaje de notificación al completar la importación.
     *
     * @param Import $import
     * @return string
     */
    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Tu importación se ha completado y ' . number_format($import->successful_rows) . ' ' . str('fila')->plural($import->successful_rows) . ' fueron importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('fila')->plural($failedRowsCount) . ' fallaron.';
        }

        return $body;
    }
}