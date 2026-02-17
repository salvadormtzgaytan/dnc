<?php

namespace Database\Seeders;

use App\Models\CatalogRegion;
use App\Models\CatalogZone;
use Illuminate\Database\Seeder;

class CatalogZoneSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/territorio.csv');

        if (!file_exists($file)) {
            $this->command->error("❌ Archivo no encontrado: $file");
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        $header = array_map('trim', array_shift($rows));

        foreach ($rows as $index => $row) {
            $data = array_combine($header, array_map('trim', $row));
            if (!$data || !isset($data['TERRITORIO'], $data['REGION'])) {
                $this->command->warn("⚠️ Fila inválida en línea " . ($index + 2));
                continue;
            }

            $region = CatalogRegion::where('name', $data['REGION'])->first();

            if (!$region) {
                $this->command->warn("❗ Región '{$data['REGION']}' no encontrada en línea " . ($index + 2));
                continue;
            }

            $zone = CatalogZone::firstOrCreate([
                'name' => $data['TERRITORIO'],
                'region_id' => $region->id,
            ]);

           // $this->command->info("✅ Territorio creado o existente: {$zone->name} (Región: {$region->name})");
        }

        $this->command->info('🌟 Seed de territorios completado con éxito.');
    }
}
