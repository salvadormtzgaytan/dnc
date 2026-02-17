<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CatalogDealershipResource;
use App\Filament\Resources\CatalogStoreResource;
use App\Filament\Resources\DncResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\GlobalAverageDynamicChart;
use App\Http\Middleware\FilamentAdminMiddleware;
use Awcodes\Overlook\OverlookPlugin;
use Awcodes\Overlook\Widgets\OverlookWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth(MaxWidth::Full)
            ->defaultThemeMode(ThemeMode::Dark)
            ->colors([
                'danger' => 'rgb(219, 42, 45)',
                'gray' => 'rgb(82, 84, 85)',
                'info' => 'rgb(26, 84, 162)',
                'primary' => 'rgb(0, 174, 239)',
                'success' => 'rgb(0, 135, 126)',
                'warning' => 'rgb(235, 139, 35)',
                'comex-cyan' => '#00AEEF',             // Mismo Cyan en hex
                'comex-black' => '#000000',             // Negro puro
                'comex-white' => '#FFFFFF',             // Blanco puro
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
                //GlobalAverageDynamicChart::class,
                //OverlookWidget::class,

                //\App\Filament\Widgets\StatsGeneralOverview::class,
                //\App\Filament\Widgets\DncProgressWidget::class,
                //\App\Filament\Widgets\AverageScoreByDncChart::class,
                //\App\Filament\Widgets\AverageScoreByDealershipChart::class,
                // \App\Filament\Widgets\CompletedDncByDateChart::class,
                //\App\Filament\Widgets\TopParticipantsChart::class,

            ])
            ->navigationGroups([
                'Reportes DNC',
                'Gestión de Usuarios',
                'Filament Shield',
                'Recursos',
                'Catálogos'
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                FilamentAdminMiddleware::class,
            ])
            ->databaseNotifications()
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
