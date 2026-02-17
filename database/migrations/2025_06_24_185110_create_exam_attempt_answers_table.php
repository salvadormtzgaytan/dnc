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
        Schema::create('exam_attempt_answers', function (Blueprint $table) {
             $table->id();
        $table->foreignId('attempt_id')
              ->constrained('exam_attempts')
              ->cascadeOnDelete();
        $table->foreignId('question_id')
              ->constrained('questions')
              ->cascadeOnDelete();
        $table->foreignId('selected_choice_id')
              ->constrained('question_choices')
              ->cascadeOnDelete();
        $table->foreignId('correct_choice_id')
              ->constrained('question_choices')
              ->cascadeOnDelete();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
