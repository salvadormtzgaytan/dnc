<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.pages', [
            'start' => [
                'title' => 'Inicio | DNC COMEX',
                'description' => 'Bienvenido al sistema de capacitación de COMEX desarrollado por Espacio 360.',
                'keywords' => 'DNC, COMEX, capacitación, espacio360',
                'og_title' => 'Inicio | DNC COMEX',
                'og_description' => 'Explora nuestra plataforma de capacitación.',
                'twitter_card' => 'summary_large_image',
                'image' => null,
            ]
        ]);

        $this->migrator->add('seo.google_analytics_id', 'G-XXXXXXXXXX');
        $this->migrator->add('seo.google_site_verification', 'tu-verificacion-site-google');
    }
};
