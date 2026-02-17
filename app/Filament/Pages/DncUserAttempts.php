<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Tables;
use Filament\Pages\Page;
use App\Models\ExamAttempt;
use App\Utils\ScoreColorHelper;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Maatwebsite\Excel\Excel as Writer;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Pages\DncUserAttemptsDetails;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class DncUserAttempts extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title          = 'Intentos del Usuario';
    protected static string  $view           = 'filament.pages.dnc-user-attempts';
    protected static ?string $slug           = 'dnc-user-attempts';

    #[\Livewire\Attributes\Url]
    public ?int   $userId    = null;
    public User   $user;
    public ?float $bestScore = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->user = User::with('profile.store')->findOrFail($this->userId);
        static::$title = 'Intentos de ' . $this->user->name;

        $this->bestScore = ExamAttempt::query()
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->max('score');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(
                fn() => ExamAttempt::query()
                    ->with('exam')
                    ->where('user_id', $this->userId)
                    ->where('status', 'completed')
                    ->orderByDesc('score')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('exam.name')
                    ->label('Examen')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('score')
                    ->label('Puntuación')
                    ->formatStateUsing(fn(int $state) => "{$state}%")
                    ->badge()
                    ->color(fn(int $state) => ScoreColorHelper::forScore($state)),

                TextColumn::make('nivel_dominio')
                    ->label('Nivel de Dominio')
                    ->getStateUsing(fn(ExamAttempt $record) => ScoreColorHelper::level($record->score))
                    ->badge()
                    ->color(fn(string $state, ExamAttempt $record) => ScoreColorHelper::forScore($record->score)),

                TextColumn::make('correct_count')
                    ->label('✔️ Correctas')
                    ->getStateUsing(fn(ExamAttempt $record) => $record->getCorrectCount())
                    ->badge()
                    ->color(fn(int $state) => ScoreColorHelper::forScore($state)),

                TextColumn::make('incorrect_count')
                    ->label('❌ Incorrectas')
                    ->getStateUsing(fn(ExamAttempt $record) => $record->getIncorrectCount())
                    ->badge()
                    ->color(fn(int $state) => ScoreColorHelper::forScore($state)),

                TextColumn::make('duration')
                    ->label('⏱️ Duración')
                    ->getStateUsing(fn(ExamAttempt $record) => $record->durationFormatted())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha de intento')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('best')
                    ->label("Sólo mejor intento ({$this->bestScore}%)")
                    ->query(fn(Builder $q) => $q->where('score', $this->bestScore)),
            ])
            ->actions([
                Action::make('viewAnswers')
                    ->label('Ver Respuestas')
                    ->icon('heroicon-o-arrow-right')
                    ->url(
                        fn(ExamAttempt $record) =>
                        DncUserAttemptsDetails::getUrl([
                            'attemptId' => $record->id,
                        ])
                    )
                    ->color('primary')
                    ->button(),
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(fn() => 'intentos-usuario-' . now()->format('Y-m-d'))
                            ->withWriterType(Writer::XLSX),
                    ]),

                Action::make('volver')
                    ->label('Volver a usuarios')
                    ->icon('heroicon-o-arrow-left')
                    ->url(route('filament.admin.pages.dnc-user-report'))
                    ->color('gray'),
            ]);
    }
}
