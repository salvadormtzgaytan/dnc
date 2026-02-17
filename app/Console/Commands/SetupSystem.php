<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Settings\SeoSettings;

class SetupSystem extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'app:setup-system {--fresh : Elimina todas las tablas y ejecuta migraciones desde cero} {--seo-permissions : Ejecuta el seeder de permisos SEO}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Instala y configura el sistema con migraciones, seeders, SEO, cachés y assets de Filament';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        if (!app()->runningInConsole()) {
            $this->error('Este comando solo puede ejecutarse desde la consola.');
            return self::FAILURE;
        }

        if (!class_exists(SeoSettings::class)) {
            $this->error('❌ La clase SeoSettings no está disponible. ¿Registraste correctamente la configuración en config/settings.php?');
            return self::FAILURE;
        }

        $start = now();

        $this->info('🔧 Iniciando instalación del sistema...');

        // 🔄 Migraciones
        if ($this->option('fresh')) {
            $this->warn('⚠️ Ejecutando migrate:fresh (eliminará todas las tablas existentes)');
            $this->call('migrate:fresh', ['--seed' => true]);
        } else {
            $this->call('migrate');
            $this->call('db:seed');
        }

        // Seeder de permisos SEO (opcional)
        if ($this->option('seo-permissions')) {
            $this->info('🔐 Ejecutando seeder de permisos SEO...');
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\PermissionSeeder']);
        }

        // 🌐 Configuración SEO inicial
        if (!Schema::hasTable('settings')) {
            $this->error('❌ La tabla "settings" no existe. ¿Ejecutaste correctamente las migraciones?');
            return self::FAILURE;
        }

        $seo = resolve(SeoSettings::class);

        if (empty($seo->pages)) {
            $this->info('📦 Registrando configuraciones SEO por primera vez...');

            $seo->pages = [
                'start' => [
                    'title' => 'Inicio | DNC COMEX',
                    'description' => 'Bienvenido al sistema de capacitación de COMEX desarrollado por Espacio 360.',
                    'keywords' => 'DNC, COMEX, capacitación, espacio360',
                    'og_title' => 'Inicio | DNC COMEX',
                    'og_description' => 'Explora nuestra plataforma de capacitación.',
                    'twitter_card' => 'summary_large_image',
                    'image' => null,
                ],
            ];

            $seo->google_analytics_id = 'G-XXXXXXXXXX';
            $seo->google_site_verification = 'tu-verificacion-site-google';
            $seo->save();

            $this->info('✅ Configuraciones SEO registradas correctamente.');
        } else {
            $this->info('ℹ️ Configuraciones SEO ya existen. No se sobrescribieron.');
        }

        // 🚀 Limpieza y optimización
        $this->call('optimize:clear');

        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        $this->call('event:cache');
        $this->info('🔗 Creando enlace simbólico de storage...');
        if (!file_exists(public_path('storage'))) {
            $this->call('storage:link');
        } else {
            $this->info('✔️ El enlace simbólico ya existe.');
        }
        
        $this->call('filament:assets');
        $this->call('livewire:publish');
        $this->call('permission:cache-reset');
        

        // 🔐 Mostrar usuario administrador
        $this->info('🔐 Usuario super_admin creado (si no existía):');
        $this->table(
            ['Nombre', 'Rol', 'Email', 'Password'],
            [[
                'Administrador General',
                'super_admin',
                'admin@example.com',
                'admin123!'
            ]]
        );

        $this->info('🎉 Sistema instalado y configurado exitosamente.');
        $this->info('🕒 Tiempo total: ' . now()->diffInSeconds($start) . ' segundos.');

        return self::SUCCESS;
    }
}