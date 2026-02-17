<?php

namespace App\Filament\Imports;

use App\Models\CatalogPosition;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogPositionImporter extends Importer
{
    protected static ?string $model = CatalogPosition::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:catalog_positions,name']),
        ];
    }

    public function resolveRecord(): ?CatalogPosition
    {
        return CatalogPosition::firstOrNew([
            'name' => $this->data['name']
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Tu importación se ha completado y '.number_format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' fueron importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
