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
        Schema::create('exam_group_overrides', function (Blueprint $table) {
            $table->id();
        $table->foreignId('exam_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_group_id')->constrained()->onDelete('cascade');
        $table->dateTime('start_at')->nullable();
        $table->dateTime('end_at')->nullable();
        $table->integer('time_limit')->nullable();
        $table->integer('max_attempts')->nullable();
        $table->timestamps();

        $table->unique(['exam_id', 'user_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_group_overrides');
    }
};
