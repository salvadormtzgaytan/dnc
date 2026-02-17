<?php

namespace App\Filament\Imports;

use App\Models\CatalogStore;
use App\Models\CatalogDealership;
use App\Models\CatalogCity;
use App\Models\CatalogState;
use App\Models\CatalogDivision;
use App\Models\CatalogRegion;
use App\Models\CatalogZone;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CatalogStoreImporter extends Importer
{
    protected static ?string $model = CatalogStore::class;

    /**
     * Define las columnas que serán importadas para las tiendas.
     *
     * @return array
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('division_name')
                ->label('División')
                ->rules(['nullable']) //, 'exists:catalog_divisions,name'
                ->fillRecordUsing(function ($record, $state) {
                    if ($state) {
                        $divisionId = CatalogDivision::where('name', $state)->value('id');
                        if (!$divisionId) {
                            throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La división '{$state}' no existe.");
                        }
                        $record->division_id = $divisionId;
                    }
                }),

            ImportColumn::make('region_name')
                ->label('Región')
                ->rules(['nullable']) //, 'exists:catalog_regions,name'
                ->fillRecordUsing(function ($record, $state) {
                    if ($state) {
                        $regionId = CatalogRegion::where('name', $state)->value('id');
                        if (!$regionId) {
                            throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La región '{$state}' no existe.");
                        }
                        $record->region_id = $regionId;
                    }
                }),

            ImportColumn::make('zone_name')
                ->label('Zona')
                ->rules(['nullable']) //, 'exists:catalog_zones,name'
                ->fillRecordUsing(function ($record, $state) {
                    if ($state) {
                        $zoneId = CatalogZone::where('name', $state)->value('id');
                        if (!$zoneId) {
                            throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La zona '{$state}' no existe.");
                        }
                        $record->zone_id = $zoneId;
                    }
                }),

            ImportColumn::make('dealership_name')
                ->label('Concesionario')
                ->requiredMapping()
                ->rules(['required']) //, 'exists:catalog_dealerships,name'
                ->fillRecordUsing(function ($record, $state) {
                    $dealershipId = CatalogDealership::where('name', $state)->value('id');
                    if (!$dealershipId) {
                        throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("El concesionario '{$state}' no existe.");
                    }
                    $record->dealership_id = $dealershipId;
                }),

            ImportColumn::make('name')
                ->label('Tienda')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'unique:catalog_stores,name']),

            ImportColumn::make('external_store_id')
                ->label('ID TDA')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('external_account_number')
                ->label('Cuenta')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('business_name')
                ->label('Razón Social')
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('state_name')
                ->label('Estado')
                ->rules(['nullable', 'exists:catalog_states,name'])
                ->fillRecordUsing(function ($record, $state) {
                    if ($state) {
                        $stateId = CatalogState::where('name', $state)->value('id');
                        if (!$stateId) {
                            throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("El estado '{$state}' no existe.");
                        }
                        $record->state_id = $stateId;
                    }
                }),

            ImportColumn::make('city_name')
                ->label('Ciudad')
                ->rules(['nullable', 'exists:catalog_cities,name'])
                ->fillRecordUsing(function ($record, $state) {
                    if ($state) {
                        $cityId = CatalogCity::where('name', $state)->value('id');
                        if (!$cityId) {
                            throw new \Filament\Actions\Imports\Exceptions\RowImportFailedException("La ciudad '{$state}' no existe.");
                        }
                        $record->city_id = $cityId;
                    }
                }),

            ImportColumn::make('address')
                ->label('Dirección')
                ->rules(['nullable', 'max:255']),
        ];
    }

    /**
     * Crea una nueva instancia de CatalogStore para cada fila importada.
     *
     * @return CatalogStore|null
     */
    public function resolveRecord(): ?CatalogStore
    {
        return new CatalogStore();
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