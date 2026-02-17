<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dnc_exam', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dnc_id')->constrained('dncs')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');

            $table->timestamps();
            $table->unique(['dnc_id', 'exam_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dnc_exam');
    }
};
