<?php

namespace App\Filament\Resources\QuestionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ChoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'choices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('text')
                    ->label('Texto de la opción')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_correct')
                    ->label('¿Es correcta?')
                    ->helperText('Marca si esta opción es una respuesta válida.')
                    ->live(debounce: 500) // Validación en vivo
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $this->validateCorrectAnswerLive($state);
                    })
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                Tables\Columns\TextColumn::make('text')->label('Opción'),
                Tables\Columns\IconColumn::make('is_correct')
                    ->boolean()
                    ->label('Correcta'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar opción')
                    ->using(function (array $data): Model {
                        $data['score'] = 1;
                        $question = $this->getOwnerRecord();
                        $nextOrder = $question->choices()->count() + 1;
                        $data['order'] = $nextOrder;
                        return $this->validateAndCreate($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->using(function (Model $record, array $data): Model {
                        return $this->validateAndUpdate($record, $data);
                    }),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->emptyStateHeading('No hay opciones registradas');
    }

    protected function validateCorrectAnswerLive(bool $isCorrect): void
    {
        $question = $this->getOwnerRecord();
        $isSingle = $question->type === 'single';
        $currentRecord = $this->getMountedTableActionRecord();

        if ($isSingle && $isCorrect) {
            $exists = $this->getRelationship()
                ->when($currentRecord, fn($q) => $q->where('id', '!=', $currentRecord->id))
                ->where('is_correct', true)
                ->exists();

            if ($exists) {
                Notification::make()
                    ->title('Error de validación')
                    ->body('Solo puedes marcar una opción correcta para preguntas de tipo única.')
                    ->danger()
                    ->send();
            }
        }
    }

    protected function validateAndCreate(array $data): Model
    {
        $this->validateSingleCorrectAnswer($data);
        return $this->getRelationship()->create($data);
    }

    protected function validateAndUpdate(Model $record, array $data): Model
    {
        $this->validateSingleCorrectAnswer($data, $record);
        $record->update($data);
        return $record;
    }

    protected function validateSingleCorrectAnswer(array $data, ?Model $current = null): void
    {
        $question = $this->getOwnerRecord();
        $isSingle = $question->type === 'single';
        $isCorrect = $data['is_correct'] ?? false;

        if ($isSingle && $isCorrect) {
            $exists = $this->getRelationship()
                ->when($current, fn($q) => $q->where('id', '!=', $current->id))
                ->where('is_correct', true)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'is_correct' => 'Solo puedes marcar una opción correcta para preguntas de tipo única.',
                ]);
            }
        }

        if (!$isCorrect) {
            $totalCorrect = $this->getRelationship()
                ->when($current, fn($q) => $q->where('id', '!=', $current->id))
                ->where('is_correct', true)
                ->count();

            if ($totalCorrect === 0) {
                throw ValidationException::withMessages([
                    'is_correct' => 'Debe haber al menos una opción correcta para esta pregunta.',
                ]);
            }
        }
    }
}