<?php

namespace App\Filament\Imports;

use App\Models\CatalogDivision;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogDivisionsImporter extends Importer
{
    protected static ?string $model = CatalogDivision::class;

    /**
     * Define las columnas que serán importadas para las divisiones.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('División')
                ->requiredMapping()
                ->rules([
                    'required',
                    'max:255',
                    'unique:catalog_divisions,name', // Valida que no esté duplicado
                ]),
        ];
    }

    /**
     * Crea una nueva instancia de CatalogDivision para cada fila importada.
     *
     * @return CatalogDivision|null
     */
    public function resolveRecord(): ?CatalogDivision
    {
        return new CatalogDivision();
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