<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_dealerships', function (Blueprint $table) {
            // Agrega la columna y la relación con catalog_zones
            $table->foreignId('zone_id')
                ->after('id')
                ->constrained('catalog_zones')
                ->onDelete('restrict');

            // Asegura unicidad por nombre y zona
            $table->unique(['name', 'zone_id'], 'dealerships_name_zone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_dealerships', function (Blueprint $table) {
            // Elimina la restricción única y la columna con su foreign
            $table->dropUnique('dealerships_name_zone_unique');
            $table->dropForeign(['zone_id']);
            $table->dropColumn('zone_id');
        });
    }
};
