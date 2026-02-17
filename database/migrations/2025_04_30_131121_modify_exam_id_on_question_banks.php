<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('set null');
            $table->unsignedBigInteger('exam_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });
    }
};
