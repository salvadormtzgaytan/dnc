<?php

namespace App\Filament\Pages;

use Filament\Tables;
use Filament\Pages\Page;
use App\Models\CatalogZone;
use App\Models\ExamAttempt;
use App\Utils\ScoreColorHelper;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class DncZoneReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static string $view = 'filament.pages.dnc-zone-report';
    protected static ?string $title = 'Reporte por Territorio (Zona)';
    protected static ?string $navigationLabel = 'Reporte DNC - Zonas';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-zone-reports';
    protected static ?int $navigationSort = 3;

    #[\Livewire\Attributes\Url(as: 'region')]
    public ?int $region = null;

    public function mount(): void
    {
        if ($this->region) {
            static::$title = 'Reporte de Zonas - Región ' . $this->region;
        }
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function () {
                $query = CatalogZone::with([
                    'region',
                    'dealerships.stores.profiles.user',
                ])
                ->withRealExamAverage();

                if ($this->region) {
                    $query->where('region_id', $this->region);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Zona')
                    ->searchable(),

                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->badge()
                    ->color('info')
                    ->visible(fn() => !$this->region),

                Tables\Columns\TextColumn::make('dealerships_count')
                    ->label('Concesionarios')
                    ->counts('dealerships')
                    ->badge(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participantes')
                    ->getStateUsing(function ($record) {
                        return $record->dealerships
                            ->flatMap(fn($dealership) => $dealership->stores)
                            ->flatMap(fn($store) => $store->profiles)
                            ->filter(fn($profile) => $profile->user !== null)
                            ->count();
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('real_exam_avg')
                    ->label('Promedio Examen')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state
                        ? number_format($state, 1) . '% (' . ScoreColorHelper::level((float)$state) . ')'
                        : 'N/A')
                    ->color(fn($state) => ScoreColorHelper::forScore((float)$state))
                    ->getStateUsing(fn($record) => $record->real_exam_avg),

                Tables\Columns\TextColumn::make('exam_attempts_count')
                    ->label('Exámenes Completados')
                    ->getStateUsing(function ($record) {
                        $userIds = $record->dealerships
                            ->flatMap(fn($dealership) => $dealership->stores)
                            ->flatMap(fn($store) => $store->profiles)
                            ->filter(fn($profile) => $profile->user !== null)
                            ->pluck('user.id');

                        return ExamAttempt::whereIn('user_id', $userIds)
                            ->where('status', 'completed')
                            ->count();
                    })
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'reporte-zonas-' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with([
                                'region',
                                'dealerships.stores.profiles.user.examAttempts'
                            ])->withRealExamAverage())
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('participants_count')
                                    ->heading('Total Participantes')
                                    ->getStateUsing(fn($record) =>
                                        $record->dealerships
                                            ->flatMap(fn($dealership) => $dealership->stores)
                                            ->flatMap(fn($store) => $store->profiles)
                                            ->filter(fn($profile) => $profile->user !== null)
                                            ->count()),

                                \pxlrbt\FilamentExcel\Columns\Column::make('real_exam_avg')
                                    ->heading('Promedio Examen (%)')
                                    ->getStateUsing(fn($record) => $record->real_exam_avg),

                                \pxlrbt\FilamentExcel\Columns\Column::make('exam_attempts_count')
                                    ->heading('Exámenes Completados')
                                    ->getStateUsing(function ($record) {
                                        $userIds = $record->dealerships
                                            ->flatMap(fn($dealership) => $dealership->stores)
                                            ->flatMap(fn($store) => $store->profiles)
                                            ->filter(fn($profile) => $profile->user !== null)
                                            ->pluck('user.id');

                                        return ExamAttempt::whereIn('user_id', $userIds)
                                            ->where('status', 'completed')
                                            ->count();
                                    }),
                            ])
                    ]),

                Action::make('verTodos')
                    ->label('Ver todas las zonas')
                    ->url(route('filament.admin.pages.dnc-zone-reports'))
                    ->icon('heroicon-o-list-bullet')
                    ->color('secondary')
                    ->visible(fn() => $this->region),
            ])
            ->actions([
                Action::make('verDetalle')
                    ->label('Ver detalle')
                    ->url(fn($record) => route('filament.admin.pages.dnc-dealership-reports', ['zone' => $record->id]))
                    ->icon('heroicon-o-arrow-right')
                    ->button()
                    ->color('primary'),
            ])
            ->defaultSort('real_exam_avg', 'desc');  // Orden inicial por promedio descendente
    }
}
