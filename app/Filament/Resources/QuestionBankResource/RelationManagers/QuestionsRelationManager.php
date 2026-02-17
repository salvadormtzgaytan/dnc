<?php

namespace App\Filament\Resources\QuestionBankResource\RelationManagers;

use App\Filament\Imports\BankQuestionsImporter;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'Preguntas';
    protected static ?string $modelLabel = 'Pregunta';
    protected static ?string $pluralModelLabel = 'Preguntas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('text')
                    ->label('Texto')
                    ->wrap()
                    ->html()
                    ->limit(50),
                Tables\Columns\TextColumn::make('segment.name')
                    ->label('Segmento')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('level.name')
                    ->label('Nivel')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'single' => 'primary',
                        'multiple' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'single' => 'Única respuesta',
                        'multiple' => 'Múltiple respuesta',
                        default => $state,
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear nueva pregunta')
                    ->modalHeading('Nueva pregunta')
                    ->modalSubmitActionLabel('Guardar pregunta')
                    ->form(fn(Form $form) => $this->form($form))
                    ->using(function (array $data): Model {
                        return $this->saveQuestionWithChoices($data);
                    }),

                Tables\Actions\ImportAction::make('importar_formato_simple')
                    ->label('Importar (formato simple)')
                    ->importer(\App\Filament\Imports\SimpleBankQuestionsImporter::class)
                    ->modalHeading('Importar preguntas (formato simple)')
                    ->options([
                        'question_bank_id' => $this->getOwnerRecord()->id,
                    ]),
                Tables\Actions\ImportAction::make('importar_completo')
                    ->label('Importar (formato completo)')
                    ->importer(BankQuestionsImporter::class)
                    ->modalHeading('Importar preguntas (formato completo)')
                    ->modalDescription(new HtmlString(
                        <<<'HTML'
            <p><a href="/storage/question-imports/bank-questions-template.csv" target="_blank" download class="text-primary-600 hover:underline">
                Descargar plantilla con columnas de opciones
            </a></p>
            <ul class="list-disc pl-5 text-sm mt-2 space-y-1">
                <li>Guarda el archivo como <strong>CSV UTF-8 (delimitado por comas)</strong> desde Excel.</li>
                <li>En Excel: <em>Archivo → Guardar como → Selecciona “CSV UTF-8 (delimitado por comas) (*.csv)”</em>.</li>
            </ul>
            HTML
                    ))
                    ->options([
                        'question_bank_id' => $this->getOwnerRecord()->id,
                    ]),

            ])
            ->actions([
                EditAction::make()
                    ->form(fn(Form $form) => $this->form($form))
                    ->using(function (Model $record, array $data): Model {
                        $question = $this->saveQuestionWithChoices($data, $record);
                        return $question;
                    }),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->title('Pregunta eliminada')
                            ->success()
                    ),
            ])
            ->emptyStateHeading('Este banco no tiene preguntas aún')
            ->emptyStateDescription('Crea tu primera pregunta haciendo clic en el botón de arriba')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear pregunta')
                    ->form(fn(Form $form) => $this->form($form)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('question_bank_id')
                    ->default(fn() => $this->getOwnerRecord()->id),
                Forms\Components\Select::make('catalog_segment_id')
                    ->label('Segmento')
                    ->relationship('segment', 'name')
                    ->searchable()
                    ->nullable()
                    ->default(fn() => \App\Models\CatalogSegment::where('name', 'Sin Segmento')->value('id')),

                Forms\Components\Select::make('catalog_level_id')
                    ->label('Nivel')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->nullable()
                    ->default(fn() => \App\Models\CatalogLevel::where('name', 'Sin Nivel')->value('id')),

                Forms\Components\TextInput::make('title')
                    ->label('Título descriptivo')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('text')
                    ->label('Texto de la pregunta')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'blockquote',
                        'bulletList',
                        'orderedList',
                    ]),
                Forms\Components\Select::make('type')
                    ->label('Tipo de pregunta')
                    ->options([
                        'single' => 'Respuesta única',
                        'multiple' => 'Respuesta múltiple',
                    ])
                    ->default('single')
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('default_score')
                    ->label('Puntaje por defecto')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.5)
                    ->default(1)
                    ->hidden(),
                Forms\Components\Toggle::make('shuffle_choices')
                    ->label('¿Reordenar opciones aleatoriamente?')
                    ->default(true)
                    ->columnSpan(1),
                Forms\Components\Repeater::make('choices')
                    ->label('Opciones de respuesta')
                    ->orderColumn('order')
                    ->schema([
                        Forms\Components\Hidden::make('id'), // <- agregar este hidden para el id
                        Forms\Components\TextInput::make('text')
                            ->label('Texto de la opción')
                            ->required()
                            ->live(onBlur: true)
                            ->columnSpan(2),
                        Forms\Components\Toggle::make('is_correct')
                            ->label('¿Correcta?')
                            ->inline()
                            ->live(),
                    ])
                    ->columns(3)
                    ->minItems(2)
                    ->required()
                    ->createItemButtonLabel('Agregar opción')
                    ->addActionLabel('Agregar otra opción')
                    ->reorderable()
                    ->collapsible()
                    ->live()
                    ->afterStateHydrated(function (Forms\Components\Repeater $component, $state, callable $set) {
                        // Usamos getMountedTableActionRecord() para obtener la pregunta al editar
                        $question = $this->getMountedTableActionRecord();
                        if ($question && empty($state)) {
                            $rows = $question->choices()
                                ->orderBy('order')
                                ->get()
                                ->map(fn($c) => [
                                    'id' => $c->id, // <-- incluye el id aquí
                                    'text' => $c->text,
                                    'is_correct' => $c->is_correct,
                                ])
                                ->toArray();

                            $set($component->getName(), $rows);
                        }
                    })
            ]);
    }

    protected function validateChoices(array $data): void
    {
        $choices = collect($data['choices'] ?? [])
            ->filter(fn($choice) => ! empty($choice['text']));
        $correctCount = $choices->where('is_correct', true)->count();
        if ($choices->count() < 2) {
            Notification::make()
                ->title('Error de validación')
                ->danger()
                ->body('Debe agregar al menos 2 opciones válidas')
                ->send();
            throw ValidationException::withMessages([
                'choices' => ['Debe agregar al menos 2 opciones válidas'],
            ]);
        }
        if ($correctCount === 0) {
            Notification::make()
                ->title('Error de validación')
                ->danger()
                ->body('Debe marcar al menos una opción como correcta')
                ->send();
            throw ValidationException::withMessages([
                'choices' => ['Debe marcar al menos una opción como correcta'],
            ]);
        }
        if (($data['type'] ?? '') === 'single' && $correctCount > 1) {
            Notification::make()
                ->title('Error de validación')
                ->danger()
                ->body('Para preguntas de respuesta única solo puede haber una opción correcta')
                ->send();
            throw ValidationException::withMessages([
                'choices' => ['Para preguntas de respuesta única solo puede haber una opción correcta'],
            ]);
        }
    }

    /**
     * Guarda pregunta y opciones conservando ids.
     * Actualiza texto/opciones existentes y solo crea nuevas si no traen id.
     */
    protected function saveQuestionWithChoices(array $data, ?Model $record = null): Model
    {
        $this->validateChoices($data);
        $choicesData = $data['choices'];
        unset($data['choices']);

        // Crear o actualizar la pregunta
        $question = $record
            ? tap($record)->update($data)
            : static::getRelationship()->getRelated()->create($data);

        // Obtener las opciones actuales (por id)
        $existingChoices = $question->choices()->get()->keyBy('id');
        $updatedChoiceIds = [];

        foreach ($choicesData as $index => $choice) {
            $choiceId = $choice['id'] ?? null;
            $existing = $choiceId ? $existingChoices->get($choiceId) : null;

            if ($existing) {
                // Si existe, actualizar valores
                $existing->update([
                    'text' => $choice['text'],
                    'is_correct' => $choice['is_correct'] ?? false,
                    'order' => $index,
                    'score' => 1,
                ]);
                $updatedChoiceIds[] = $existing->id;
            } else {
                // Si no existe, crear nueva opción
                $newChoice = $question->choices()->create([
                    'text' => $choice['text'],
                    'is_correct' => $choice['is_correct'] ?? false,
                    'order' => $index,
                    'score' => 1,
                ]);
                $updatedChoiceIds[] = $newChoice->id;
            }
        }

        // Eliminar opciones que ya no están en el formulario
        $question->choices()->whereNotIn('id', $updatedChoiceIds)->delete();

        return $question;
    }
}
