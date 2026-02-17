<?php

namespace App\Filament\Pages;

use App\Models\CatalogRegion;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Utils\ScoreColorHelper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

/**
 * Clase DncRegionReport
 *
 * Esta página de Filament muestra un reporte de las regiones DNC, permitiendo visualizar:
 * - El número de participantes por región.
 * - El promedio de calificaciones de exámenes completados por región.
 * - El número total de exámenes completados por región.
 * - La división a la que pertenece la región.
 *
 * Además, permite exportar el reporte a Excel y navegar al detalle de cada región.
 */
class DncRegionReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.dnc-region-report';
    protected static ?string $title = 'Reporte por Región';
    protected static ?string $navigationLabel = 'Reporte DNC - Regiones';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-region-reports';
    protected static ?int $navigationSort = 2;

    #[\Livewire\Attributes\Url(as: 'division')]
    public ?int $division = null;

    /**
     * Define la tabla principal del reporte de regiones.
     *
     * - Muestra columnas con ID, nombre de la región, división, cantidad de participantes,
     *   promedio de exámenes y cantidad de exámenes completados.
     * - Permite exportar los datos a Excel.
     * - Permite navegar al detalle de cada región.
     *
     * @param Tables\Table $table
     * @return Tables\Table
     */
    public function table(Tables\Table $table): Tables\Table
{
    return $table
        ->query(function () {
            $query = CatalogRegion::with('division')
                ->withRealExamAverage();

            if ($this->division) {
                $query->where('division_id', $this->division);
            }

            return $query;
        })
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Región')
                ->searchable(),

            Tables\Columns\TextColumn::make('division.name')
                ->label('División')
                ->sortable()
                ->badge(),

            Tables\Columns\TextColumn::make('participants_count')
                ->label('Participantes')
                ->getStateUsing(function ($record) {
                    return User::whereHas('profile.store.zone.region', fn ($q) =>
                        $q->where('id', $record->id)
                    )->count();
                })
                ->badge(),

            Tables\Columns\TextColumn::make('real_exam_avg')
                ->label('Promedio Examen')
                ->sortable()
                ->formatStateUsing(fn ($state) => $state
                    ? number_format($state, 1) . ' % (' . ScoreColorHelper::level((float) $state) . ')'
                    : 'N/A')
                ->color(fn ($state) => ScoreColorHelper::forScore((float) $state))
                ->getStateUsing(fn ($record) => $record->real_exam_avg),

            Tables\Columns\TextColumn::make('exam_attempts_count')
                ->label('Exámenes Completados')
                ->getStateUsing(function ($record) {
                    $userIds = User::whereHas('profile.store.zone.region', fn ($q) =>
                        $q->where('id', $record->id)
                    )->pluck('id');

                    return ExamAttempt::whereIn('user_id', $userIds)
                        ->where('status', 'completed')
                        ->count();
                })
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
        ])
        ->headerActions([
            ExportAction::make()
                ->label('Exportar a Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(fn () => 'reporte-regiones-' . now()->format('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                ]),
        ])
        ->actions([
            Action::make('verDetalle')
                ->label('Ver detalle')
                ->url(fn ($record) => route('filament.admin.pages.dnc-zone-reports', ['region' => $record->id]))
                ->icon('heroicon-o-arrow-right')
                ->button()
                ->color('primary'),
        ])
        ->defaultSort('real_exam_avg', 'desc');
}

}