<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dnc_user_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dnc_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->datetime('custom_start_date')->nullable();
            $table->datetime('custom_end_date')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['dnc_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dnc_user_overrides');
    }
};
