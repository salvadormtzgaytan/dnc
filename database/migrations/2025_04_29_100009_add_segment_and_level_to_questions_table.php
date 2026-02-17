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
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('catalog_segment_id')
                ->nullable() // ✅ se permite null
                ->constrained()
                ->nullOnDelete(); // ✅ al borrar el catálogo, pone null
        
            $table->foreignId('catalog_level_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['catalog_segment_id']);
            $table->dropForeign(['catalog_level_id']);
            $table->dropColumn(['catalog_segment_id', 'catalog_level_id']);
        });
    }
};
