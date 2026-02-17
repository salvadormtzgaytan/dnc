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
        //
        Schema::table('catalog_stores', function (Blueprint $table) {
            $table->string('external_store_id')->nullable();
            $table->string('external_account_number')->nullable();
            $table->string('business_name')->nullable();
            $table->string('address')->nullable();
        
            $table->foreignId('division_id')->nullable()->constrained('catalog_divisions')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('catalog_regions')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('catalog_zones')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('catalog_states')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('catalog_cities')->nullOnDelete();
            $table->foreignId('dealership_id')->nullable()->constrained('catalog_dealerships')->nullOnDelete();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
