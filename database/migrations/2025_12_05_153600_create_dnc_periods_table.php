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
        Schema::create('dnc_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dnc_id')->constrained()->onDelete('cascade');
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->string('period_name')->nullable(); // Ej: "Noviembre 2024", "Diciembre 2024 - Enero 2025"
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['dnc_id', 'is_current']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dnc_periods');
    }
};