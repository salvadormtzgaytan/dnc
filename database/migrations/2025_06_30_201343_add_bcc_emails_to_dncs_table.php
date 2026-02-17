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
        Schema::table('dncs', function (Blueprint $table) {
            $table->text('bcc_emails')
                  ->nullable()
                  ->after('name')              // <-- aquí cambiamos a 'name'
                  ->comment('Lista de correos en BCC, separados por comas');
        });
    }

    /**
     * Revertir las migraciones.
     */
    public function down(): void
    {
        Schema::table('dncs', function (Blueprint $table) {
            $table->dropColumn('bcc_emails');
        });
    }
};
