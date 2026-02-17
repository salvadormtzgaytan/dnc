<?php

namespace App\Filament\Resources;

use App\Utils\UploadLimits;
use Carbon\Carbon;
use Filament\Forms;
use App\Models\Exam;
use Filament\Forms\Components\FileUpload;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\ExamResource\Pages;
use App\Filament\Resources\ExamResource\RelationManagers\FeedbacksRelationManager;
use App\Filament\Resources\ExamResource\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\ExamResource\RelationManagers\UserOverridesRelationManager;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Exámenes';
    protected static ?string $modelLabel = 'Examen';
    protected static ?string $pluralModelLabel = 'Exámenes';
    protected static ?string $navigationGroup = 'Recursos';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                DateTimePicker::make('start_at')
                    ->label('Fecha de apertura')
                    ->native(true)
                    ->seconds(false)
                    ->displayFormat('d/m/Y H:i')
                    ->helperText('Déjalo vacío si el examen debe estar disponible de inmediato.')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        self::validateExamDates($state, $get('end_at'), $set);
                    }),

                DateTimePicker::make('end_at')
                    ->label('Fecha de cierre')
                    ->native(true)
                    ->seconds(false)
                    ->displayFormat('d/m/Y H:i')
                    ->helperText('Establece esta fecha solo si quieres un cierre. Requiere una fecha de apertura.')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        self::validateExamDates($get('start_at'), $state, $set);
                    })
                    ->rules([
                        fn(callable $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            if ($value && !$get('start_at')) {
                                $fail('No puedes establecer fecha de cierre sin fecha de apertura');
                            }
                        },
                        'after_or_equal:start_at'
                    ]),

                Grid::make()
                    ->schema([

                        TimePicker::make('time_limit')
                        ->label('Tiempo límite')
                        ->seconds(false)
                        ->withoutSeconds()
                        ->nullable()
                        ->native(false)
                        ->columnSpan(['lg' => 2, 'xl' => 1])
                        ->default(fn ($record) => $record && $record->time_limit ? now()->startOfDay()->addSeconds($record->time_limit) : null)
                        ->afterStateHydrated(function ($component, $state) {
                            if ($state) {
                                $component->state(now()->startOfDay()->addSeconds($state));
                            }
                        })
                        ->dehydrateStateUsing(function ($state) {
                            if (!$state) return null;
                    
                            // Asegura que sea instancia de Carbon
                            $time = $state instanceof \DateTimeInterface
                                ? Carbon::instance($state)
                                : Carbon::parse($state);
                    
                            return $time->diffInSeconds(Carbon::createFromTime(0, 0));
                        })
                        ->dehydrated(true)
                        ->name('time_limit'),


                        TextInput::make('max_attempts')
                            ->label('Intentos')
                            ->numeric()
                            ->default(1)
                            ->columnSpan(['lg' => 1]),

                        TextInput::make('pass_score')
                            ->label('Aprobación')
                            ->numeric()
                            ->default(80)
                            ->columnSpan(['lg' => 1]),

                        TextInput::make('questions_per_page')
                            ->label('Preguntas x página')
                            ->numeric()
                            ->default(5)
                            ->columnSpan(['lg' => 1]),

                        Select::make('navigation_method')
                            ->label('Navegación')
                            ->options(Exam::NAVIGATION_METHODS)
                            ->default('sequential')
                            ->columnSpan(['lg' => 2, 'xl' => 1])
                    ])
                    ->columns(['lg' => 5])
                    ->columnSpanFull(),

                /* 
                                Forms\Components\TextInput::make('max_score')
                                    ->label('Calificación máxima')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(100.00), */
                Hidden::make('max_score')
                    ->default(100.0),
                Toggle::make('shuffle_questions')
                    ->label('¿Aleatorizar orden de las preguntas?')
                    ->default(true)
                    ->helperText('Si está activado, el orden de las preguntas se mostrará de forma aleatoria para cada intento.'),
                Toggle::make('enabled')
                    ->label('¿Habilitado?')
                    ->default(true)
                    ->helperText('Si está desactivado, los usuarios no podrán acceder al examen.'),
                FileUpload::make('image_path')
                    ->label('Imagen del examen')
                    ->disk('public')
                    ->directory('exams')
                    ->image()
                    ->maxSize(UploadLimits::maxSize('image'))
                    ->acceptedFileTypes(UploadLimits::mimeTypes('image'))
                    ->imagePreviewHeight('150')
                    ->downloadable()
                    ->openable()
                    ->nullable()
                    ->columnSpanFull(),

            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('enabled')
                    ->label('Activo')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Disponibilidad')
                    ->state(function ($record): string {
                        if (is_null($record->start_at) && is_null($record->end_at)) {
                            return 'always_available';
                        } elseif (!is_null($record->start_at) && !is_null($record->end_at)) {
                            return 'date_range';
                        } elseif (!is_null($record->start_at)) {
                            return 'from_date';
                        }
                        return 'invalid';
                    })
                    ->formatStateUsing(function ($state, $record) {
                        return match ($state) {
                            'always_available' => 'Siempre disponible',
                            'date_range' => $record->start_at->format('d/m/Y H:i') . ' - ' . $record->end_at->format('d/m/Y H:i'),
                            'from_date' => 'Desde ' . $record->start_at->format('d/m/Y H:i'),
                            default => 'Configuración inválida'
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'always_available' => 'success',
                            'date_range' => 'info',
                            'from_date' => 'warning',
                            default => 'danger'
                        };
                    })
                    ->sortable(['start_at', 'end_at'])
                    ->description(function ($record) {
                        return !is_null($record->end_at) ? 'Hasta: ' . $record->end_at->format('d/m/Y H:i') : null;
                    }),

                TextColumn::make('time_limit_formatted')
                    ->label('Límite de Tiempo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Sin límite' => 'gray',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('max_attempts')
                    ->label('Intentos permitidos')
                    ->formatStateUsing(fn($state) => $state ?: 'Sin límite')
                    ->sortable(),
                TextColumn::make('navigation_method')
                    ->label('Metodo de navegación')->state(function ($record) {
                        return $record->navigation_method === 'sequential' ? 'Secuencial' : 'Libre';
                    })
                    ->badge()
                    ->color(fn($state) => $state === 'sequential' ? 'warning' : 'success'),

                TextColumn::make('pass_score')
                    ->label('Puntuación aprobatoria')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shuffle_questions')
                    ->label('Preguntas aleatorias')
                    ->state(function ($record) {
                        return $record->shuffle_questions ? 'Sí' : 'No';
                    })
                    ->badge()
                    ->color(fn($state) => $state === 'Sí' ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function validateDateRange(?string $start, ?string $end): void
    {
        if ($start && $end && Carbon::parse($start)->gt(Carbon::parse($end))) {
            Notification::make()
                ->title('Error en fechas')
                ->body('La fecha de cierre no puede ser menor a la fecha de apertura.')
                ->danger()
                ->send();
        }
    }

    protected static function validateExamDates(?string $start, ?string $end, callable $set): void
    {
        // Si hay fecha de cierre pero no de apertura, limpiar fecha de cierre
        if ($end && !$start) {
            $set('end_at', null);
            Notification::make()
                ->title('Configuración inválida')
                ->body('No puedes establecer fecha de cierre sin fecha de apertura')
                ->danger()
                ->send();
            return;
        }

        // Validar que fecha de cierre no sea menor que apertura
        if ($start && $end && Carbon::parse($start)->gt(Carbon::parse($end))) {
            Notification::make()
                ->title('Error en fechas')
                ->body('La fecha de cierre no puede ser menor a la fecha de apertura.')
                ->danger()
                ->send();
        }
    }



    public static function getRelations(): array
    {
        return [
            FeedbacksRelationManager::class,
            UserOverridesRelationManager::class,
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }

}