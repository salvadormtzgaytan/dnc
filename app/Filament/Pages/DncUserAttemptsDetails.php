<?php

namespace App\Filament\Pages;

use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class DncUserAttemptsDetails extends Page implements HasTable
{
    use InteractsWithTable;
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static string $view = 'filament.pages.dnc-user-attempts-details';
    protected static ?string $slug = 'dnc-user-attempts-details';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Reportes DNC';
    protected static ?string $navigationLabel = 'Detalles Intentos Usuarios DNC';
    protected static ?int $navigationSort = 7;
    protected static ?string $model = ExamAttemptAnswer::class;

    #[Url]
    public ?int $attemptId = null;
    public ?ExamAttempt $attempt = null;

    public function mount(): void
    {
        if ($this->attemptId) {
            $this->attempt = ExamAttempt::with(['user.profile.store', 'exam.dncs'])
                ->findOrFail($this->attemptId);

            static::$title = sprintf(
                'Respuestas: %s — Examen: %s (Intento #%d)',
                $this->attempt->user->name,
                $this->attempt->exam->name,
                $this->attempt->id
            );
        }
    }

    protected function getTableQuery(): Builder
    {
        $query = ExamAttemptAnswer::query()
            ->with([
                'attempt.user.profile.store',
                'attempt.exam.dncs',
                'question',
                'selectedChoice',
                'correctChoice',
            ]);

        if ($this->attemptId) {
            $query->where('attempt_id', $this->attemptId);
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('attempt.user.name')
                ->label('Usuario')
                ->sortable()
                ->visible(!$this->attemptId),

            TextColumn::make('attempt.user.profile.store.name')
                ->label('Tienda')
                ->sortable()
                ->placeholder('—')
                ->visible(!$this->attemptId),

            TextColumn::make('attempt.exam.name')
                ->label('Examen')
                ->sortable()
                ->visible(!$this->attemptId),

            TextColumn::make('question.text')
                ->label('Pregunta')
                ->wrap()
                ->searchable(),

            TextColumn::make('selectedChoice.text')
                ->label('Tu respuesta')
                ->wrap(),

            TextColumn::make('correctChoice.text')
                ->label('Respuesta correcta')
                ->wrap(),

            IconColumn::make('is_correct')
                ->label('Correcta')
                ->boolean()
                ->getStateUsing(
                    fn(ExamAttemptAnswer $r) =>
                    $r->selected_choice_id === $r->correct_choice_id
                )
                ->trueIcon('heroicon-s-check-circle')
                ->falseIcon('heroicon-s-x-circle')
                ->colors([
                    'success' => fn($state) => $state,
                    'danger' => fn($state) => !$state,
                ]),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Filter::make('correct')
                ->label('Solo correctas')
                ->query(
                    fn(Builder $q) =>
                    $q->whereColumn('selected_choice_id', 'correct_choice_id')
                ),

            Filter::make('incorrect')
                ->label('Solo incorrectas')
                ->query(
                    fn(Builder $q) =>
                    $q->whereColumn('selected_choice_id', '!=', 'correct_choice_id')
                ),

            Filter::make('attempt')
                ->label('Filtrar por intento')
                ->form([
                    \Filament\Forms\Components\Select::make('attempt')
                        ->label('Intento')
                        ->options(ExamAttempt::pluck('id', 'id'))
                        ->searchable(),
                ])
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['attempt'])) {
                        $query->where('attempt_id', $data['attempt']);
                    }
                    return $query;
                })
                ->visible(!$this->attemptId),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make('export')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make('Respuestas')
                        ->fromTable()
                        ->withFilename(
                            $this->attemptId
                                ? "respuestas_intento_{$this->attemptId}_" . now()->format('Ymd_His')
                                : 'respuestas_todos_' . now()->format('Ymd_His')
                        )
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX),
                ]),

            Tables\Actions\Action::make('back')
                ->label($this->attemptId ? 'Volver a Intentos' : 'Volver atrás')
                ->icon('heroicon-o-arrow-left')
                ->url(
                    fn() => $this->attemptId
                        ? route('filament.admin.pages.dnc-user-attempts', ['userId' => $this->attempt->user_id])
                        : url()->previous()
                )
                ->color('gray'),
        ];
    }
}
