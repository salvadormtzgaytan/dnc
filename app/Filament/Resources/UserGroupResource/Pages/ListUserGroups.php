<?php
namespace App\Filament\Resources\UserGroupResource\Pages;

use App\Filament\Resources\UserGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListUserGroups extends ListRecords
{
    protected static string $resource = UserGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Crear grupo'),
            
            ExportAction::make()
                ->label('Exportar Todos')
                ->icon('heroicon-o-arrow-down-tray') // Icono actualizado en Heroicons v2
                ->color('success')
                ->exports([
                    ExcelExport::make()
                        ->fromModel() // 📌 Exporta TODOS los registros (sin paginación)
                        ->withFilename(fn () => 'user_groups_' . now()->format('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX) // Opcional: formato XLSX
                ])
        ];
    }
}