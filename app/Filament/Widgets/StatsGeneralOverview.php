<?php

namespace App\Filament\Widgets;

use App\Models\Dnc;
use App\Models\User;
use App\Models\CatalogStore;
use App\Models\CatalogDealership;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsGeneralOverview extends BaseWidget
{
    
    protected static ?string $pollingInterval = '30s'; // Auto-refresco cada 30 segundos
    protected static bool $isLazy = true; // Carga diferida para mejor rendimiento
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        return [
            Stat::make('Concesionarias', CatalogDealership::count())
                ->description('Total registradas')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary')
                ->chart([7, 3, 5, 10, 15])
                ->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hover:shadow-2xl transition-shadow',
                    'title' => 'Concesionarias registradas en el sistema'
                ]),

            Stat::make('Tiendas', CatalogStore::count())
                ->description('Total activas')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success')
                ->chart([15, 10, 5, 3, 7])
                ->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hover:shadow-2xl transition-shadow',
                    'title' => 'Tiendas activas en el sistema'
                ]),

            Stat::make('DNCs', Dnc::query()->where('is_active', true)->count())
                ->description('Activas')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning')
                ->chart([3, 5, 7, 10, 15])
                ->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hover:shadow-2xl transition-shadow',
                    'title' => 'Documentos No Conformes activos'
                ]),

            Stat::make('Usuarios', User::query()->where('deleted_at', null)->count())
                ->description('Activos')
                ->descriptionIcon('heroicon-o-users')
                ->color('info')
                ->chart([10, 15, 7, 5, 3])
                ->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg hover:shadow-2xl transition-shadow',
                    'title' => 'Usuarios activos en el sistema'
                ]),
        ];
    }
}