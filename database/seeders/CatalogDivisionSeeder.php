<?php

namespace Database\Seeders;

use App\Models\CatalogDivision;
use Illuminate\Database\Seeder;

class CatalogDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('data/division.csv');

        if (!file_exists($file)) {
            $this->command->error("❌ Archivo no encontrado: $file");
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        $header = array_map('trim', array_shift($rows));

        $divisionKey = $header[0] ?? 'DIVISION';

        foreach ($rows as $index => $row) {
            $divisionName = trim($row[0]);

            if (!$divisionName) {
                $this->command->warn("⚠️ División vacía en la línea " . ($index + 2));
                continue;
            }

            $division = CatalogDivision::firstOrCreate([
                'name' => $divisionName,
            ]);

           // $this->command->info("✅ División creada o existente: {$division->name}");
        }

        $this->command->info('🌟 Seed de divisiones completado con éxito.');
    }
}
