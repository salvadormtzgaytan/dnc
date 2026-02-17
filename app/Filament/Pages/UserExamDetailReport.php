<?php

namespace App\Filament\Pages;

use App\Models\Dnc;
use App\Models\ExamAttempt;
use App\Utils\ScoreColorHelper;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class UserExamDetailReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon  = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Detalle de Intentos por Usuario';
    protected static string $view             = 'filament.pages.user-exam-detail-report';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $title           = 'Detalle de Intentos por Usuario';
    protected static ?int $navigationSort     = 1;

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.rfc')
                    ->label('RFC')
                    ->searchable(),

                TextColumn::make('dnc_name')
                    ->label('DNC')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('exam.name')
                    ->label('Examen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attempt')
                    ->label('Intento')
                    ->numeric(),

                TextColumn::make('score')
                    ->label('Calificación')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2, '.', '') . '%')
                    ->color(fn ($state) => ScoreColorHelper::forScore($state)),

                TextColumn::make('id')
                    ->label('Nivel de dominio')
                    ->formatStateUsing(fn ($state, $record) => ScoreColorHelper::level($record->score))
                    ->badge()
                    ->color(fn ($state, $record) => ScoreColorHelper::forScore($record->score))
                    ->sortable(false),

                TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d M Y H:i'),

                TextColumn::make('finished_at')
                    ->label('Fin')
                    ->dateTime('d M Y H:i'),

                TextColumn::make('elapsed_time')
                    ->label('Duración'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => [
                        'completed'   => 'Completado',
                        'in_progress' => 'En progreso',
                    ][$state] ?? $state)
                    ->color(fn ($state) => [
                        'completed'   => 'success',
                        'in_progress' => 'warning',
                    ][$state] ?? 'gray')
                    ->icon(fn ($state) => [
                        'completed'   => 'heroicon-o-check-circle',
                        'in_progress' => 'heroicon-o-clock',
                    ][$state] ?? 'heroicon-o-question-mark-circle'),

                TextColumn::make('user.profile.store.external_store_id')
                    ->label('#Tienda')
                    ->sortable(),

                TextColumn::make('user.profile.store.name')
                    ->label('Tienda')
                    ->sortable(),

                TextColumn::make('user.profile.store.division.name')
                    ->label('División')
                    ->sortable(),

                TextColumn::make('user.profile.store.region.name')
                    ->label('Región')
                    ->sortable(),

                TextColumn::make('user.profile.store.zone.name')
                    ->label('Zona')
                    ->sortable(),

                TextColumn::make('user.profile.store.dealership.name')
                    ->label('Concesionario')
                    ->sortable(),

                TextColumn::make('user.profile.store.state.name')
                    ->label('Estado')
                    ->sortable(),

                TextColumn::make('user.profile.store.city.name')
                    ->label('Ciudad')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dnc_id')
                    ->label('DNC')
                    ->options(Dnc::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('exam.dncs', fn ($q) => $q->where('dncs.id', $data['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('exam_id')
                    ->label('Examen')
                    ->relationship('exam', 'name'),

                Tables\Filters\Filter::make('finished_at')
                    ->label('Fecha de Finalización')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['from'])) {
                            $query->whereDate('finished_at', '>=', $data['from']);
                        }
                        if (!empty($data['until'])) {
                            $query->whereDate('finished_at', '<=', $data['until']);
                        }
                    }),
            ])
            ->groups([
                Tables\Grouping\Group::make('user.email')
                    ->label('Email')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),

                Tables\Grouping\Group::make('dnc_name')
                    ->label('DNC')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),

                Tables\Grouping\Group::make('exam.name')
                    ->label('Examen')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn ($record) => $record->exam->name),
            ])
            ->defaultGroup('user.email')
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar a Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn () => 'DetalleIntentosUsuario_' . now()->format('Y-m-d'))
                            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
                    ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return ExamAttempt::query()
            ->with([
                'user' => fn ($query) => $query->with([
                    'profile.store' => fn ($query) => $query->with([
                        'division',
                        'region',
                        'zone',
                        'dealership',
                        'state',
                        'city',
                    ]),
                ]),
                'exam.dncs',
            ])
            ->select('exam_attempts.*')
            ->selectSub(
                DB::table('exam_attempts as ea')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ea.user_id', 'exam_attempts.user_id')
                    ->whereColumn('ea.exam_id', 'exam_attempts.exam_id')
                    ->where('ea.id', '<=', DB::raw('exam_attempts.id')),
                'attempt',
            )
            ->addSelect([
                'dnc_name' => Dnc::query()
                    ->select('dncs.name')
                    ->join('dnc_exam', 'dnc_exam.dnc_id', '=', 'dncs.id')
                    ->whereColumn('dnc_exam.exam_id', 'exam_attempts.exam_id')
                    ->limit(1),
            ])
            ->whereHas('user.roles', fn ($q) => $q->where('name', 'participante'));
    }
}
