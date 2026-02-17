<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CatalogStore;
use App\Models\CatalogState;
use App\Models\CatalogCity;
use App\Models\CatalogDivision;
use App\Models\CatalogRegion;
use App\Models\CatalogZone;
use App\Models\CatalogDealership;

class CatalogStoreSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/maestro_de_tiendas.csv');

        if (!file_exists($file)) {
            $this->command->error("❌ Archivo no encontrado: $file");
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        $headers = array_map('trim', array_shift($rows));
        $total = count($rows);

        $this->command->info("📄 Procesando $total filas del archivo CSV...");

        foreach ($rows as $index => $row) {
            $data = array_combine($headers, array_map('trim', $row));

            $stateName        = mb_strtoupper($data['ESTADO']);
            $externalStoreId  = $data['IDTDA'];
            $externalAccount  = $data['CUENTA'];
            $businessName     = mb_strtoupper($data['RAZONSOCIAL']);
            $divisionName     = mb_strtoupper($data['DIVISION']);
            $regionName       = mb_strtoupper($data['REGION']);
            $zoneName         = mb_strtoupper($data['TERRITORIO']);
            $storeName        = mb_strtoupper($data['TIENDA']);
            $dealershipName   = mb_strtoupper($data['CONCESIONARIO']);

            // Estado
            $state = CatalogState::firstOrCreate(['name' => $stateName]);

            // Ciudad = nombre de tienda (provisional)
            $city = CatalogCity::firstOrCreate([
                'state_id' => $state->id,
                'name' => $storeName,
            ]);

            // División
            $division = CatalogDivision::firstOrCreate(['name' => $divisionName]);

            // Región
            $region = CatalogRegion::firstOrCreate([
                'division_id' => $division->id,
                'name' => $regionName,
            ]);

            // Territorio
            $zone = CatalogZone::firstOrCreate([
                'region_id' => $region->id,
                'name' => $zoneName,
            ]);

            // Concesionaria
            $dealership = CatalogDealership::firstOrCreate([
                'name' => $dealershipName,
                'zone_id' => $zone->id,
            ]);

            // Tienda
            CatalogStore::updateOrCreate(
                ['external_store_id' => $externalStoreId],
                [
                    'name' => $storeName,
                    'external_account_number' => $externalAccount,
                    'business_name' => $businessName,
                    'address' => null,
                    'division_id' => $division->id,
                    'region_id' => $region->id,
                    'zone_id' => $zone->id,
                    'dealership_id' => $dealership->id,
                    'state_id' => $state->id,
                    'city_id' => $city->id,
                ]
            );

            // Mostrar % de avance cada 50 filas
            if ($index % 50 === 0 || $index + 1 === $total) {
                $percent = number_format(($index + 1) * 100 / $total, 1);
                $this->command->info("⏳ Progreso: $percent% - $storeName");
            }
        }

        $this->command->info("✅ Catálogo de tiendas completado correctamente.");
    }
}
