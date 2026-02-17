<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Si quieres registrar bindings, helpers, etc. aquí
    }

    public function boot(): void
    {
        /**
         * ⚙️ Título y descripción global del sitio
         */
        SEOMeta::setTitleDefault(config('app.name'));
        SEOMeta::setDescription(config('seotools.meta.defaults.description', env('SEO_DESCRIPTION')));

        /**
         * 🏷️ Palabras clave (si las manejas)
         */
        if ($keywords = env('SEO_KEYWORDS')) {
            SEOMeta::addKeyword(explode(',', $keywords));
        }

        /**
         * 🔗 Canonical URL global
         */
        SEOMeta::setCanonical(url()->current());

        /**
         * 📘 OpenGraph para redes sociales (Facebook, LinkedIn)
         */
        OpenGraph::setSiteName(config('app.name'));
        OpenGraph::setUrl(url()->current());
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('locale', 'es_MX'); // ✅ esta es la forma correcta

        /**
         * 🐦 Twitter Card
         */
        TwitterCard::setSite('@MiEmpresa');
        TwitterCard::setType('summary_large_image');
        TwitterCard::setTitle(config('app.name'));
        TwitterCard::setDescription(config('seotools.meta.defaults.description'));

        /**
         * 📊 Google Analytics (GA4)
         * Este script no se inyecta aquí, pero puedes dejar una variable de vista global
         */
        view()->share('googleAnalyticsId', env('GOOGLE_ANALYTICS_ID'));

        /**
         * 🔍 Google Search Console: meta tag de verificación
         */
        view()->share('googleSiteVerification', env('GOOGLE_SITE_VERIFICATION'));

        /**
         * 🌐 Idioma para HTML tag <html lang="">
         */
        app()->setLocale('es');
    }
}
