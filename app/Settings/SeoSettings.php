<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public array $pages;

    public string $google_analytics_id;
    public string $google_site_verification;

    public static function group(): string
    {
        return 'seo';
    }

    public function for(string $page): array
    {
        return $this->pages[$page] ?? [];
    }
}
