<?php

namespace Database\Seeders;

use App\Models\CatalogDivision;
use App\Models\CatalogRegion;
use Illuminate\Database\Seeder;

class CatalogRegionSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/region.csv');

        if (!file_exists($file)) {
            $this->command->error("❌ Archivo no encontrado: $file");
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        $header = array_map('trim', array_shift($rows));

        foreach ($rows as $index => $row) {
            $data = array_combine($header, array_map('trim', $row));
            if (!$data || !isset($data['REGION'], $data['DIVISION'])) {
                $this->command->warn("⚠️ Fila inválida en línea " . ($index + 2));
                continue;
            }

            $division = CatalogDivision::where('name', $data['DIVISION'])->first();

            if (!$division) {
                $this->command->warn("❗ División '{$data['DIVISION']}' no encontrada en línea " . ($index + 2));
                continue;
            }

            $region = CatalogRegion::firstOrCreate([
                'name' => $data['REGION'],
                'division_id' => $division->id,
            ]);

           // $this->command->info("✅ Región creada o existente: {$region->name} (División: {$division->name})");
        }

        $this->command->info('🌟 Seed de regiones completado con éxito.');
    }
}
