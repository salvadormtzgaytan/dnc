<?php

namespace App\Filament\Resources\ExamResource\RelationManagers;

use App\Models\ExamFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;

class FeedbacksRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbacks';
    protected static ?string $title = 'Retroalimentaciones Globales';
    protected static ?string $modelLabel = 'Retroalimentación Global';
    protected static ?string $pluralModelLabel = 'Retroalimentaciones Globales';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('min_score')
                    ->label('Puntuación mínima')
                    ->numeric()
                    ->required()
                    ->step(0.5)
                    ->minValue(0)
                    ->maxValue(100)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $this->validateScoreRange($state, $get('max_score'), $set);
                    }),

                Forms\Components\TextInput::make('max_score')
                    ->label('Puntuación máxima')
                    ->numeric()
                    ->required()
                    ->step(0.5)
                    ->minValue(0)
                    ->maxValue(100)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $this->validateScoreRange($get('min_score'), $state, $set);
                    }),

                RichEditor::make('feedback')
                    ->label('Mensaje de retroalimentación')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('min_score')
                    ->label('Desde')
                    ->numeric(
                        decimalPlaces: 1,
                        decimalSeparator: '.',
                        thousandsSeparator: ''
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_score')
                    ->label('Hasta')
                    ->numeric(
                        decimalPlaces: 1,
                        decimalSeparator: '.',
                        thousandsSeparator: ''
                    )
                    ->sortable(),

                TextColumn::make('feedback')
                    ->label('Mensaje')
                    ->html()       // interpreta las etiquetas HTML
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                // Filtros opcionales
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Retroalimentación')
                    ->modalHeading('Crear nueva retroalimentación')
                    ->using(function (array $data): Model {
                        $this->validateOverride($data);
                        return $this->getRelationship()->create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Editar retroalimentación')
                    ->using(function (Model $record, array $data): Model {
                        $this->validateOverride($data, $record);
                        $record->update($data);
                        return $record;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Eliminar retroalimentación'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay retroalimentaciones definidas')
            ->emptyStateDescription('Crea una retroalimentación para este examen');
    }

    protected function validateScoreRange($min, $max, callable $set): void
    {
        if (!is_numeric($min) || !is_numeric($max)) {
            return;
        }

        $min = (float)$min;
        $max = (float)$max;

        if ($min > $max) {
            $set('max_score', $min);
            Notification::make()
                ->title('Ajuste automático')
                ->body('La puntuación máxima no puede ser menor que la mínima. Se ajustó automáticamente.')
                ->warning()
                ->send();
            return;
        }

        $this->validateRangeOverlap($min, $max);
    }

    protected function validateRangeOverlap(float $min, float $max): bool
    {
        $examId = $this->getOwnerRecord()->id;
        $recordId = $this->getMountedTableActionRecord()?->id;

        $overlapping = ExamFeedback::where('exam_id', $examId)
            ->when($recordId, fn($query) => $query->where('id', '!=', $recordId))
            ->where(function ($query) use ($min, $max) {
                $query->where(function ($q) use ($min, $max) {
                    $q->where('min_score', '<=', $min)
                        ->where('max_score', '>=', $min);
                })->orWhere(function ($q) use ($min, $max) {
                    $q->where('min_score', '<=', $max)
                        ->where('max_score', '>=', $max);
                })->orWhere(function ($q) use ($min, $max) {
                    $q->where('min_score', '<', $max)
                        ->where('max_score', '>', $min);
                });
            })
            ->exists();

        if ($overlapping) {
            Notification::make()
                ->title('Rango duplicado')
                ->body('Este rango de puntuaciones se solapa con otra retroalimentación existente.')
                ->danger()
                ->send();
            return false;
        }

        return true;
    }

    protected function validateOverride(array $data, ?Model $record = null): void
    {
        $min = (float)($data['min_score'] ?? 0);
        $max = (float)($data['max_score'] ?? 0);

        // Validación básica de rango
        if ($min > $max) {
            throw ValidationException::withMessages([
                'max_score' => 'La puntuación máxima debe ser igual o mayor que la mínima.',
            ]);
        }

        // Validación de solapamiento
        if (!$this->validateRangeOverlap($min, $max)) {
            throw ValidationException::withMessages([
                'min_score' => 'Este rango se solapa con una retroalimentación existente.',
                'max_score' => 'Este rango se solapa con una retroalimentación existente.',
            ]);
        }
    }
}
