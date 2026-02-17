<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        //Carbon::setLocale(env('locale', 'en')); // Formato en español
        //date_default_timezone_set(env('APP_TIMEZONE', 'UTC')); // Ajusta según tu zona
        //\Illuminate\Support\Facades\Blade::componentNamespace('Illuminate\\Mail\\Markdown', 'mail');
        
        // Registrar observers
        \App\Models\Dnc::observe(\App\Observers\DncObserver::class);
    }
}
