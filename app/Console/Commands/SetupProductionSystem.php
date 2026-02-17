<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Settings\SeoSettings;
use Illuminate\Support\Facades\File;
class SetupProductionSystem extends Command
{
    protected $signature = 'app:setup-production {--seed}';
    protected $description = 'Prepara la aplicación para producción (migraciones, seeds, cachés, SEO, permisos y assets)';

    public function handle(): int
    {
        $this->info('🚀 Preparando entorno para producción...');

        // Verificar entorno
        if (!app()->environment('production')) {
            $this->warn('⚠️ Este comando está optimizado para ejecutarse en APP_ENV=production.');
        }

        // Ejecutar migraciones con opción de seed
        $this->call('migrate', [
            '--force' => true,
            '--seed' => $this->option('seed'),
        ]);

        // Registrar configuración SEO si no existe
        if (Schema::hasTable('settings')) {
            $seo = resolve(SeoSettings::class);

            if (empty($seo->pages)) {
                $this->info('📦 Registrando configuración SEO por defecto...');

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

                $this->info('✅ Configuración SEO inicial aplicada.');
            } else {
                $this->info('ℹ️ Configuración SEO ya existe. No se modificó.');
            }
        }

        // Verificar y crear symlink de storage
        $storageLink = public_path('storage');

        if (!File::isDirectory($storageLink) || !is_link($storageLink)) {
            $this->warn('🔗 Enlace simbólico "public/storage" no existe o es una carpeta normal.');

            // Si existe como carpeta normal, la eliminamos primero
            if (File::exists($storageLink) && !is_link($storageLink)) {
                File::deleteDirectory($storageLink);
                $this->info('🗑️ Carpeta "public/storage" eliminada.');
            }

            $this->call('storage:link');
            $this->info('✅ Enlace simbólico "public/storage" creado correctamente.');
        } else {
            $this->info('🔗 Enlace simbólico "public/storage" ya existe y es válido.');
        }

        // Limpiar cachés antiguas
        $this->call('optimize:clear');

        // Recompilar y cachear configuración
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        $this->call('event:cache');

        // Publicar assets Filament y Livewire (compatible con Livewire v3)
        $this->call('filament:assets');
        $this->call('livewire:publish');

        // Generar permisos Filament Shield (necesario para seeders iniciales)
        $this->call('shield:generate');

        // Reset de caché de permisos Spatie (Filament Shield)
        $this->call('permission:cache-reset');

        $this->info('✅ Entorno de producción preparado correctamente.');

        return self::SUCCESS;
    }
}
