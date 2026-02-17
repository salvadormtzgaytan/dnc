<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
            UsersTableSeeder::class,
            PermissionSeeder::class,
            UpdateRoleLabelsSeeder::class,
            
            // Catálogos geográficos
            CatalogStateSeeder::class,
            CatalogCitySeeder::class,

            // Catálogos jerárquicos de tiendas (en orden)
            CatalogDivisionSeeder::class,
            CatalogRegionSeeder::class,
            CatalogZoneSeeder::class,
            CatalogDealershipSeeder::class,
            CatalogStoreSeeder::class,

            //Catálogos para agrupación de preguntas
            CatalogLevelSeeder::class,
            CatalogSegmentSeeder::class
        ]);
    }
}
