<?php

namespace App\Filament\Resources\DncResource\RelationManagers;

use Filament\Forms;
use App\Models\Exam;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;

class ExamsRelationManager extends RelationManager
{
    // Nombre de la relación definida en el modelo Dnc
    protected static string $relationship = 'exams';


    protected static ?string $title = 'Exámenes asignados';
    protected static ?string $modelLabel = 'Examen';
    protected static ?string $pluralModelLabel = 'Exámenes';

    // Título de cada registro en la tabla
    protected static ?string $recordTitleAttribute = 'name';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre del examen')
                ->disabled() // como se está adjuntando, no se edita aquí
                ->maxLength(255),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Examen')
                    ->url(fn($record) => route('filament.admin.resources.exams.edit', ['record' => $record]))
                    ->color('primary')
                    ->icon('heroicon-m-pencil-square')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('# Preguntas')
                    ->counts('questions') // << Esta línea es clave
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('Inicio')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asignar examen')
                    ->preloadRecordSelect(function ($action) {
                        // Obtener exámenes disponibles
                        $availableExams = Exam::query()
                            ->whereDoesntHave('dncs', function ($query) {
                            $query->where('dnc_id', $this->ownerRecord->id);
                        })
                            ->pluck('name', 'id');

                        // Si no hay disponibles, mostrar notificación
                        if ($availableExams->isEmpty()) {
                            Notification::make()
                                ->title('No hay exámenes disponibles para asignar')
                                ->warning()
                                ->send();

                            return [];
                        }

                        return $availableExams;
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Quitar'),
            ]);
    }
}
