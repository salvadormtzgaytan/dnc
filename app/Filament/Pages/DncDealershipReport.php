<?php

namespace App\Filament\Pages;

use App\Models\CatalogDealership;
use App\Models\ExamAttempt;
use App\Utils\ScoreColorHelper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\Builder;

class DncDealershipReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static string $view = 'filament.pages.dnc-dealership-report';
    protected static ?string $title = 'Reporte de Concesionarios';
    protected static ?string $navigationLabel = 'Reporte DNC - Concesionarios';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-dealership-reports';
    protected static ?int $navigationSort = 4;

    #[\Livewire\Attributes\Url(as: 'zone')]
    public ?int $zone = null;

    public function mount(): void
    {
        if ($this->zone) {
            static::$title = 'Reporte de Concesionarios - Zona ' . $this->zone;
        }
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function () {
                $query = CatalogDealership::with(['zone', 'stores.profiles.user.examAttempts'])
                    ->withRealExamAverage();

                if ($this->zone) {
                    $query->where('zone_id', $this->zone);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Concesionario')
                    ->searchable(),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zona')
                    ->badge()
                    ->color('info')
                    ->visible(fn () => !$this->zone),

                Tables\Columns\TextColumn::make('stores_count')
                    ->label('Tiendas')
                    ->counts('stores')
                    ->badge(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participantes')
                    ->getStateUsing(function ($record) {
                        return $record->stores
                            ->flatMap(fn ($store) => $store->profiles)
                            ->filter(fn ($profile) => $profile->user !== null)
                            ->count();
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('real_exam_avg')
                    ->label('Promedio Examen')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state
                        ? number_format($state, 1) . '% (' . ScoreColorHelper::level((float) $state) . ')'
                        : 'N/A')
                    ->color(fn ($state) => ScoreColorHelper::forScore((float) $state))
                    ->getStateUsing(fn ($record) => $record->real_exam_avg),

                Tables\Columns\TextColumn::make('exam_attempts_count')
                    ->label('Exámenes Completados')
                    ->getStateUsing(function ($record) {
                        $userIds = $record->stores
                            ->flatMap(fn ($store) => $store->profiles)
                            ->filter(fn ($profile) => $profile->user !== null)
                            ->pluck('user.id');

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
                            ->withFilename(fn () => 'reporte-concesionarios-' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->modifyQueryUsing(fn ($query) => $query->with(['zone', 'stores.profiles.user.examAttempts'])
                                ->withRealExamAverage())
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('participants_count')
                                    ->heading('Total Participantes')
                                    ->getStateUsing(fn ($record) => $record->stores
                                        ->flatMap(fn ($store) => $store->profiles)
                                        ->filter(fn ($profile) => $profile->user !== null)
                                        ->count()),

                                \pxlrbt\FilamentExcel\Columns\Column::make('real_exam_avg')
                                    ->heading('Promedio Examen (%)')
                                    ->getStateUsing(fn ($record) => $record->real_exam_avg),

                                \pxlrbt\FilamentExcel\Columns\Column::make('exam_attempts_count')
                                    ->heading('Exámenes Completados')
                                    ->getStateUsing(function ($record) {
                                        $userIds = $record->stores
                                            ->flatMap(fn ($store) => $store->profiles)
                                            ->filter(fn ($profile) => $profile->user !== null)
                                            ->pluck('user.id');

                                        return ExamAttempt::whereIn('user_id', $userIds)
                                            ->where('status', 'completed')
                                            ->count();
                                    }),
                            ]),
                    ]),
                Action::make('verTodos')
                    ->label('Ver todos los concesionarios')
                    ->url(route('filament.admin.pages.dnc-dealership-reports'))
                    ->icon('heroicon-o-list-bullet')
                    ->color('secondary')
                    ->visible(fn () => $this->zone),
            ])
            ->actions([
                Action::make('verDetalle')
                    ->label('Ver detalle')
                    ->url(fn ($record) => route('filament.admin.pages.dnc-store-reports', ['dealership' => $record->id]))
                    ->icon('heroicon-o-arrow-right')
                    ->button()
                    ->color('primary'),
            ])
            ->defaultSort('real_exam_avg', 'desc');
    }
}
