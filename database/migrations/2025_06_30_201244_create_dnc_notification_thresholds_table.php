<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     */
    public function up(): void
    {
        Schema::create('notification_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dnc_id')
                  ->constrained('dncs')
                  ->cascadeOnDelete()
                  ->comment('Clave foránea a la DNC');
            $table->unsignedInteger('days_before')
                  ->comment('Días antes del vencimiento para enviar la notificación');
            $table->timestamps();
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_thresholds');
    }
};
