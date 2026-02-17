<?php

namespace App\Filament\Imports;

use App\Models\CatalogRegion;
use App\Models\CatalogDivision;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogRegionsImporter extends Importer
{
    protected static ?string $model = CatalogRegion::class;

    /**
     * Define las columnas que serán importadas para las regiones.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('division_name')
                ->label('División')
                ->requiredMapping()
                ->rules(['required'])
                ->fillRecordUsing(function ($record, $state) {
                    $divisionId = CatalogDivision::where('name', $state)->value('id');
                    if (!$divisionId) {
                        throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La división '{$state}' no existe.");
                    }
                    $record->division_id = $divisionId;
                }),

            ImportColumn::make('name')
                ->label('Región')
                ->requiredMapping()
                ->rules([
                    'required',
                    'max:255',
                    'unique:catalog_regions,name', // Valida que no esté duplicado
                ]),
        ];
    }

    /**
     * Crea una nueva instancia de CatalogRegion para cada fila importada.
     *
     * @return CatalogRegion|null
     */
    public function resolveRecord(): ?CatalogRegion
    {
        return new CatalogRegion();
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