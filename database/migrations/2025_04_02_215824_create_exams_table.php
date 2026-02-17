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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del examen
        
            // ⏱️ Tiempo
            $table->timestamp('start_at')->nullable(); // Abrir el examen
            $table->timestamp('end_at')->nullable();   // Cerrar el examen
            $table->unsignedInteger('time_limit')->nullable(); // en minutos
        
            // 🎯 Intentos y calificación
            $table->unsignedInteger('max_attempts')->default(1);
            $table->decimal('pass_score', 5, 2)->default(70); // Calificación mínima para aprobar
            $table->decimal('max_score', 5, 2)->default(100); // Calificación máxima
        
            // 🎨 Diseño
            $table->unsignedTinyInteger('questions_per_page')->default(1);
            $table->enum('navigation_method', ['sequential', 'free'])->default('sequential');
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
