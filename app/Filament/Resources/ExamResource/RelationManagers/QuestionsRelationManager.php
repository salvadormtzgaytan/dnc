<?php

namespace App\Filament\Resources\ExamResource\RelationManagers;

use App\Models\Question;
use App\Models\QuestionBank;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Preguntas asignadas';
    protected static ?string $modelLabel = 'Pregunta';
    protected static ?string $pluralModelLabel = 'Preguntas';

    protected ?int $defaultBankId = null;
    public function mount(): void
    {
        $this->defaultBankId = $this->getOwnerRecord()->question_bank_id ?? null;
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Nombre'),
                Tables\Columns\TextColumn::make('type')->label('Tipo'),
                Tables\Columns\TextColumn::make('segment.name')->label('Segmento'),
                Tables\Columns\TextColumn::make('level.name')->label('Nivel'),
                Tables\Columns\IconColumn::make('shuffle_choices')
                    ->label('Aleatorio')
                    ->boolean(),
                Tables\Columns\TextColumn::make('default_score')->label('Puntaje'),
            ])
            ->headerActions([
            /* Tables\Actions\AttachAction::make()
                    ->label('Agregar preguntas existentes')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title', 'text']), */
                Tables\Actions\Action::make('attachAllFromBank')
                    ->label('Vincular banco de preguntas')
                    ->form([
                        Forms\Components\Select::make('question_bank_id')
                            ->label('Banco de preguntas')
                            ->options(QuestionBank::pluck('name', 'id'))
                            ->required()
                            ->default(fn () => $this->defaultBankId),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $exam = $livewire->getOwnerRecord();
                        $questions = Question::where('question_bank_id', $data['question_bank_id'])->pluck('id');

                        $exam->questions()->syncWithoutDetaching($questions);

                        Notification::make()
                            ->title('Preguntas vinculadas')
                            ->body('Se han vinculado todas las preguntas del banco seleccionado.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Quitar'),
            ])
            ->emptyStateHeading('Este examen aún no tiene preguntas asignadas.');
    }
}
