<?php

namespace App\Filament\Resources\ExamResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class UserOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'userOverrides';
    protected static ?string $title = 'Anulaciones por Usuario';
    protected static ?string $modelLabel = 'Anulación por Usuario';
    protected static ?string $pluralModelLabel = 'Anulaciones por Usuario';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Usuario')
                ->relationship('user', 'email')
                ->required()
                ->disabled(fn(?Model $record) => $record !== null) // No permitir cambiar usuario en edición
                ->searchable()
                ->preload(),
            Forms\Components\TimePicker::make('time_limit')
                ->label('Límite de tiempo personalizado')
                ->seconds(true) // hh:mm:ss
                ->nullable()
                ->native(false)
                ->helperText('Puedes establecer un límite de tiempo diferente al del examen global (opcional).'),
            Forms\Components\DateTimePicker::make('start_at')
                ->label('Fecha de apertura personalizada')
                ->displayFormat('d/m/Y H:i')
                ->native(false)
                ->seconds(false)
                ->nullable()
                ->live(debounce: 500)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $this->validateDateRange($state, $get('end_at'));
                }),

            Forms\Components\DateTimePicker::make('end_at')
                ->label('Fecha de cierre personalizada')
                ->displayFormat('d/m/Y H:i')
                ->native(false)
                ->seconds(false)
                ->nullable()
                ->live(debounce: 500)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    $this->validateDateRange($get('start_at'), $state);
                }),

            Forms\Components\TextInput::make('max_attempts')
                ->label('Intentos permitidos')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->live(debounce: 500)
                ->afterStateUpdated(function ($state) {
                    $this->validateAttempts($state);
                }),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Usuario')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Fecha de apertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('Fecha de cierre')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_limit_formatted')
                    ->label('Límite de Tiempo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_attempts')
                    ->label('Intentos'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(fn(array $data) => $this->validateAndCreate($data))
                    ->successNotificationTitle('Anulación creada exitosamente'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(fn(Model $record, array $data) => $this->validateAndUpdate($record, $data))
                    ->successNotificationTitle('Anulación actualizada exitosamente'),

                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Anulación eliminada exitosamente'),
            ])
            ->emptyStateHeading('No hay anulaciones por usuario registradas');
    }

    protected function validateAndCreate(array $data): Model
    {
        $this->validateOverride($data);
        return $this->getRelationship()->create($data);
    }

    protected function validateAndUpdate(Model $record, array $data): Model
    {
        $this->validateOverride($data, $record);
        $record->update($data);
        return $record;
    }

    protected function validateDateRange(?string $start, ?string $end): void
    {
        if ($start && $end && Carbon::parse($start)->gt(Carbon::parse($end))) {
            Notification::make()
                ->title('Error en fechas')
                ->body('La fecha de cierre no puede ser menor a la fecha de apertura.')
                ->danger()
                ->send();
        }
    }

    protected function validateAttempts(?int $attempts): void
    {
        $exam = $this->getOwnerRecord();

        if ($attempts && $exam->max_attempts && $attempts < $exam->max_attempts) {
            Notification::make()
                ->title('Error en intentos')
                ->body('Los intentos personalizados no pueden ser menores a los intentos globales del examen (mínimo: ' . $exam->max_attempts . ')')
                ->danger()
                ->send();
        }
    }

    protected function validateOverride(array $data, ?Model $record = null): void
    {
        $userId = $data['user_id'] ?? null;
        $start = $data['start_at'] ?? null;
        $end = $data['end_at'] ?? null;
        $attempts = $data['max_attempts'] ?? null;
        $exam = $this->getOwnerRecord();

        // Validación de rango de fechas
        if ($start && $end && Carbon::parse($start)->gt(Carbon::parse($end))) {
            throw ValidationException::withMessages([
                'end_at' => 'La fecha de cierre no puede ser menor a la fecha de apertura.',
            ]);
        }

        // Validación de intentos mínimos
        if ($attempts && $exam->max_attempts && $attempts < $exam->max_attempts) {
            throw ValidationException::withMessages([
                'max_attempts' => 'Los intentos personalizados no pueden ser menores a los intentos globales del examen (mínimo: ' . $exam->max_attempts . ')',
            ]);
        }

        // Validación de una única anulación por usuario
        if ($userId) {
            $existingOverride = $this->getRelationship()
                ->where('user_id', $userId)
                ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                ->exists();

            if ($existingOverride) {
                throw ValidationException::withMessages([
                    'user_id' => 'Este usuario ya tiene una anulación configurada para este examen.',
                ]);
            }
        }
    }
}