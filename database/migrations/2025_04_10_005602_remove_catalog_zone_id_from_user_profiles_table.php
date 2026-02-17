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
        Schema::table('user_profiles', function (Blueprint $table) {
            //
            if (Schema::hasColumn('user_profiles', 'catalog_zone_id')) {
                $table->dropForeign(['catalog_zone_id']);
                $table->dropColumn('catalog_zone_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreignId('catalog_zone_id')
                ->nullable()
                ->constrained('catalog_zones')
                ->nullOnDelete();
        });
    }
};
