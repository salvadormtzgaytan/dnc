<?php

namespace App\Filament\Pages;

use App\Models\CatalogDivision;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Utils\ScoreColorHelper;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\DB;

class DncDivisionReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.dnc-division-report';
    protected static ?string $title = 'Reporte por División';
    protected static ?string $navigationLabel = 'Reporte DNC - Divisiones';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $slug = 'dnc-division-reports';
    protected static ?int $navigationSort = 2;

    #[\Livewire\Attributes\Url(as: 'division')]
    public ?int $division = null;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function () {
                $query = CatalogDivision::query()->withRealExamAverage();

                if ($this->division) {
                    $query->where('id', $this->division);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('División')
                    ->searchable(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participantes')
                    ->getStateUsing(function ($record) {
                        return User::whereHas('profile', function ($query) use ($record) {
                            $query->whereHas('store', function ($q) use ($record) {
                                $q->where('division_id', $record->id);
                            });
                        })->count();
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('real_exam_avg')
                    ->label('Promedio Examen')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state
                        ? number_format($state, 1) . ' % (' . ScoreColorHelper::level((float)$state) . ')'
                        : 'N/A')
                    ->color(fn($state) => ScoreColorHelper::forScore((float)$state))
                    ->getStateUsing(fn($record) => $record->real_exam_avg),

                Tables\Columns\TextColumn::make('exam_attempts_count')
                    ->label('Exámenes Completados')
                    ->getStateUsing(function ($record) {
                        $userIds = User::whereHas('profile', function ($query) use ($record) {
                            $query->whereHas('store', function ($q) use ($record) {
                                $q->where('division_id', $record->id);
                            });
                        })->pluck('id');

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
                            ->withFilename(fn () => 'reporte-divisiones-' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                    ]),
            ])
            ->actions([
                Action::make('verDetalle')
                    ->label('Ver detalle')
                    ->url(fn($record) => route('filament.admin.pages.dnc-region-reports', ['division' => $record->id]))
                    ->icon('heroicon-o-arrow-right')
                    ->button()
                    ->color('primary'),
            ])
            ->defaultSort('real_exam_avg', 'desc');
    }
}
