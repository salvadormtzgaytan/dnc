<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Utils\ScoreColorHelper;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class UserTrainingReport extends Page implements Tables\Contracts\HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Reporte de Usuarios y Avance';

    protected static string $view = 'filament.pages.user-training-report';

    protected static ?string $navigationGroup = 'Reportes DNC';

    protected static ?string $title = 'Reporte de Usuarios y Avance';

    protected static ?int $navigationSort = 0;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('rfc')->label('RFC'),
                TextColumn::make('profile.store.external_store_id')->label('#Tienda')->searchable(),
                TextColumn::make('profile.store.name')->label('Tienda'),
                TextColumn::make('profile.store.division.name')->label('División'),
                TextColumn::make('profile.store.region.name')->label('Región'),
                TextColumn::make('profile.store.zone.name')->label('Zona'),
                TextColumn::make('profile.store.dealership.name')->label('Concesionario'),
                TextColumn::make('profile.store.state.name')->label('Estado'),
                TextColumn::make('profile.store.city.name')->label('Ciudad'),

                TextColumn::make('avg_score_all')
                    ->label('Prom. Total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', '').'%'),

                TextColumn::make('domain_level')
                    ->label('Nivel de Dominio')
                    ->badge()
                    ->color(fn ($state) => ScoreColorHelper::forScore($state))
                    ->formatStateUsing(fn ($state) => ScoreColorHelper::level($state)),

                TextColumn::make('avg_score_completed')
                    ->label('Prom. Realizados')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', '').'%'),

                TextColumn::make('progress')
                    ->label('% Avance')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', '').'%'),

                TextColumn::make('completed_exams')->label('Exámenes Hechos')->numeric()->sortable(),
                TextColumn::make('total_exams')->label('Exámenes Asignados')->numeric()->sortable(),

                TextColumn::make('last_exam_date')
                    ->label('Último Examen')
                    ->sortable()
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin exámenes'),

                TextColumn::make('last_period_name')
                    ->label('Período DNC')
                    ->sortable()
                    ->placeholder('Sin período'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dncs')
                    ->label('Filtrar por DNC')
                    ->relationship('dncs', 'name')->preload(),

                Tables\Filters\SelectFilter::make('period')
                    ->label('Filtrar por Período')
                    ->options(function () {
                        return \App\Models\DncPeriod::query()
                            ->select('period_name')
                            ->selectRaw('GROUP_CONCAT(id) as period_ids')
                            ->selectRaw('MAX(start_date) as max_start_date')
                            ->groupBy('period_name')
                            ->orderBy('max_start_date', 'desc')
                            ->get()
                            ->pluck('period_name', 'period_ids')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'] ?? null, function ($q, $periodIds) {
                            $periodIdsArray = explode(',', $periodIds);

                            return $q->whereExists(function ($subQuery) use ($periodIdsArray) {
                                $subQuery->select('ea.id')
                                    ->from('exam_attempts as ea')
                                    ->whereColumn('ea.user_id', 'users.id')
                                    ->whereIn('ea.dnc_period_id', $periodIdsArray)
                                    ->where('ea.status', 'completed');
                            })->orWhereExists(function ($subQuery) use ($periodIdsArray) {
                                $subQuery->select('dua.id')
                                    ->from('dnc_user_assignments as dua')
                                    ->join('dnc_periods as dp', 'dua.dnc_id', '=', 'dp.dnc_id')
                                    ->whereColumn('dua.user_id', 'users.id')
                                    ->whereIn('dp.id', $periodIdsArray)
                                    ->whereBetween('dua.created_at', [\DB::raw('dp.start_date'), \DB::raw('dp.end_date')]);
                            });
                        });
                    }),

                Tables\Filters\Filter::make('finished_at')
                    ->label('Rango de Fechas de Finalización')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->placeholder('Fecha inicial'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->placeholder('Fecha final'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, function ($q, $date) {
                                return $q->whereExists(function ($subQuery) use ($date) {
                                    $subQuery->select('exam_attempts.id')
                                        ->from('exam_attempts')
                                        ->whereColumn('exam_attempts.user_id', 'users.id')
                                        ->where('exam_attempts.status', 'completed')
                                        ->whereDate('exam_attempts.finished_at', '>=', $date);
                                });
                            })
                            ->when($data['until'] ?? null, function ($q, $date) {
                                return $q->whereExists(function ($subQuery) use ($date) {
                                    $subQuery->select('exam_attempts.id')
                                        ->from('exam_attempts')
                                        ->whereColumn('exam_attempts.user_id', 'users.id')
                                        ->where('exam_attempts.status', 'completed')
                                        ->whereDate('exam_attempts.finished_at', '<=', $date);
                                });
                            });
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Desde: '.\Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Hasta: '.\Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'ReporteUsuarios_'.now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                            ->withColumns([
                                Column::make('name')->heading('Nombre'),
                                Column::make('email')->heading('Email'),
                                Column::make('rfc')->heading('RFC'),
                                Column::make('profile.store.external_store_id')->heading('ID Tienda'),
                                Column::make('profile.store.name')->heading('Tienda'),
                                Column::make('profile.store.division.name')->heading('División'),
                                Column::make('profile.store.region.name')->heading('Región'),
                                Column::make('profile.store.zone.name')->heading('Zona'),
                                Column::make('profile.store.dealership.name')->heading('Concesionario'),
                                Column::make('profile.store.state.name')->heading('Estado'),
                                Column::make('profile.store.city.name')->heading('Ciudad'),
                                Column::make('avg_score_all')->heading('Prom. Total'),
                                Column::make('avg_score_completed')->heading('Prom. Realizados'),
                                Column::make('progress')->heading('% Avance'),
                                Column::make('completed_exams')->heading('Exámenes Hechos'),
                                Column::make('total_exams')->heading('Exámenes Asignados'),
                                Column::make('last_exam_date')->heading('Último Examen'),
                                Column::make('last_period_name')->heading('Período DNC'),
                            ]),
                    ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->with([
                'profile.store.division',
                'profile.store.region',
                'profile.store.zone',
                'profile.store.dealership',
                'profile.store.state',
                'profile.store.city',
            ])
            ->whereHas('roles', fn ($q) => $q->where('name', 'participante'))
            ->select('users.*')
            ->selectSub(function ($query) {
                return $query->from('dnc_user_assignments as dua')
                    ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                    ->leftJoin('exam_attempts as ea', function ($join) {
                        $join->on('ea.exam_id', '=', 'de.exam_id')
                            ->on('ea.user_id', '=', 'dua.user_id')
                            ->where('ea.status', '=', 'completed');
                    })
                    ->whereColumn('dua.user_id', 'users.id')
                    ->selectRaw('ROUND(SUM(COALESCE(ea.score, 0)) / COUNT(DISTINCT de.exam_id), 2)');
            }, 'avg_score_all')
            ->selectSub(function ($query) {
                return $query->from('exam_attempts')
                    ->selectRaw('AVG(score)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', 'completed');
            }, 'avg_score_completed')
            ->selectSub(function ($query) {
                return $query->from('dnc_user_assignments as dua')
                    ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                    ->leftJoin('exam_attempts as ea', function ($join) {
                        $join->on('ea.exam_id', '=', 'de.exam_id')
                            ->on('ea.user_id', '=', 'dua.user_id')
                            ->where('ea.status', '=', 'completed');
                    })
                    ->whereColumn('dua.user_id', 'users.id')
                    ->selectRaw('ROUND(COUNT(ea.id) * 100.0 / COUNT(DISTINCT de.exam_id), 2)');
            }, 'progress')
            ->selectSub(function ($query) {
                return $query->from('dnc_user_assignments as dua')
                    ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                    ->whereColumn('dua.user_id', 'users.id')
                    ->selectRaw('COUNT(DISTINCT de.exam_id)');
            }, 'total_exams')
            ->selectSub(function ($query) {
                return $query->from('dnc_user_assignments as dua')
                    ->join('dnc_exam as de', 'dua.dnc_id', '=', 'de.dnc_id')
                    ->join('exam_attempts as ea', function ($join) {
                        $join->on('ea.exam_id', '=', 'de.exam_id')
                            ->on('ea.user_id', '=', 'dua.user_id')
                            ->where('ea.status', '=', 'completed');
                    })
                    ->whereColumn('dua.user_id', 'users.id')
                    ->selectRaw('COUNT(DISTINCT de.exam_id)');
            }, 'completed_exams')
            ->selectRaw('(
                SELECT ROUND(SUM(COALESCE(ea.score, 0)) / NULLIF(COUNT(DISTINCT de.exam_id), 0), 2)
                FROM dnc_user_assignments dua
                JOIN dnc_exam de ON dua.dnc_id = de.dnc_id
                LEFT JOIN exam_attempts ea ON ea.exam_id = de.exam_id AND ea.user_id = dua.user_id AND ea.status = "completed"
                WHERE dua.user_id = users.id
            ) as domain_level')
            ->selectSub(function ($query) {
                return $query->from('exam_attempts')
                    ->selectRaw('MAX(finished_at)')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', 'completed');
            }, 'last_exam_date')
            ->selectSub(function ($query) {
                return $query->selectRaw('COALESCE(
                    (
                        SELECT dp.period_name
                        FROM exam_attempts ea
                        JOIN dnc_periods dp ON ea.dnc_period_id = dp.id
                        WHERE ea.user_id = users.id AND ea.status = "completed"
                        ORDER BY ea.finished_at DESC
                        LIMIT 1
                    ),
                    (
                        SELECT dp.period_name
                        FROM dnc_user_assignments dua
                        JOIN dnc_periods dp ON dua.dnc_id = dp.dnc_id
                        WHERE dua.user_id = users.id
                        AND dua.created_at BETWEEN dp.start_date AND dp.end_date
                        ORDER BY dua.created_at DESC
                        LIMIT 1
                    )
                )');
            }, 'last_period_name');
    }
}
