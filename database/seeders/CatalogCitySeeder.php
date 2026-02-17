<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CatalogCity;
use App\Models\CatalogState;

class CatalogCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/estados-municipios.json')), true);

        foreach ($data as $state => $cities) {
            $stateName = strtoupper($state);
            $stateModel = CatalogState::where('name', $stateName)->first();

            if (!$stateModel) {
                continue;
            }

            foreach ($cities as $city) {
                CatalogCity::firstOrCreate([
                    'state_id' => $stateModel->id,
                    'name' => strtoupper($city),
                ]);
            }
        }
    }
}
