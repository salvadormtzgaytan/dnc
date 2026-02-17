<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->string('status')->default('in_progress'); // in_progress, completed, cancelled

            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('max_score')->nullable();

            $table->json('question_order')->nullable(); // orden fijo para el intento
            $table->json('answers')->nullable();        // respuestas parciales o completas

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
