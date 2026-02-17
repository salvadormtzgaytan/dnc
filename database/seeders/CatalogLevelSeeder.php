<?php

namespace Database\Seeders;

use App\Models\CatalogLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $levels = [
            'BASICO',
            'INTERMEDIO',
            'AVANZADO',
        ];

        foreach ($levels as $level) {
            CatalogLevel::firstOrCreate(['name' => $level]);
        }
    }
}
