<?php

namespace Database\Seeders;

use App\Models\CatalogSegment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $segments = [
            'Retail',
            'B2B',
            'VDT',
        ];

        foreach ($segments as $segment) {
            CatalogSegment::firstOrCreate(['name' => $segment]);
        }
    }
}
