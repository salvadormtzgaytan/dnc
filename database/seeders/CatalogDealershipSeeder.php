<?php

namespace Database\Seeders;

use App\Models\CatalogDealership;
use App\Models\CatalogZone;
use Illuminate\Database\Seeder;

class CatalogDealershipSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/concesionarios.csv');

        if (!file_exists($file)) {
            $this->command->error("❌ Archivo no encontrado: $file");
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        $header = array_map('trim', array_shift($rows));

        foreach ($rows as $index => $row) {
            $data = array_combine($header, array_map('trim', $row));
            if (!$data || !isset($data['CONCESIONARIO'], $data['TERRITORIO'])) {
                $this->command->warn("⚠️ Fila inválida en línea " . ($index + 2));
                continue;
            }

            $zone = CatalogZone::where('name', $data['TERRITORIO'])->first();

            if (!$zone) {
                $this->command->warn("❗ Territorio '{$data['TERRITORIO']}' no encontrado en línea " . ($index + 2));
                continue;
            }

            $dealership = CatalogDealership::firstOrCreate([
                'name' => $data['CONCESIONARIO'],
                'zone_id' => $zone->id,
            ]);

            //$this->command->info("✅ Concesionario creado o existente: {$dealership->name} (Zona: {$zone->name})");
        }

        $this->command->info('🌟 Seed de concesionarios completado con éxito.');
    }
}
