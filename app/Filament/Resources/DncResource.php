<?php

namespace App\Filament\Resources;

use App\Models\Dnc;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Utils\UploadLimits;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\DncResource\Pages;
use App\Filament\Resources\DncResource\RelationManagers\ExamsRelationManager;
use App\Filament\Resources\DncResource\RelationManagers\ThresholdsRelationManager;
use App\Filament\Resources\DncResource\RelationManagers\AssignedUsersRelationManager;

class DncResource extends Resource
{
    protected static ?string $model = Dnc::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'DNCs';
    protected static ?string $modelLabel = 'DNC';
    protected static ?string $pluralModelLabel = 'DNCs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre de la DNC')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('start_date')
                ->label('Fecha de inicio')
                ->helperText('Déjalo vacío si estará disponible de inmediato.')
                ->timezone(config('app.timezone'))
                ->seconds(false)
                ->live()
                ->nullable(),

            Forms\Components\DateTimePicker::make('end_date')
                ->label('Fecha de fin')
                ->helperText('Establece esta fecha solo si quieres un cierre. Requiere una fecha de apertura.')
                ->timezone(config('app.timezone'))
                ->seconds(false)
                ->nullable()
                ->live()
                ->afterOrEqual('start_date'),

            Forms\Components\Toggle::make('is_active')
                ->label('Activa')
                ->default(true),

            Forms\Components\Toggle::make('update_current_period')
                ->label('Actualizar período actual (no crear nuevo)')
                ->helperText('Si está marcado, actualiza las fechas del período actual. Si no, crea un nuevo período.')
                ->default(true)
                ->live()
                ->dehydrated(true), // SÍ enviar en el request
            // Campo de imagen
            FileUpload::make('image_path')
                ->label('Imagen ilustrativa')
                ->disk('public')
                ->directory('dncs')
                ->image()
                ->maxSize(UploadLimits::maxSize('image'))
                ->acceptedFileTypes(UploadLimits::mimeTypes('image'))
                ->imagePreviewHeight('150')
                ->downloadable()
                ->openable()
                ->nullable()
                ->columnSpanFull(),
            Textarea::make('bcc_emails')
                ->label('BCC (opcional)')
                ->helperText('Lista de correos separados por comas; se normalizarán \' , \' y \';\' por comas.')
                ->rows(3)
                ->columnSpan('full')

                // 1) Normalizar separadores y limpiar duplicados
                ->dehydrateStateUsing(function (?string $state): ?string {
                    if (! $state) {
                        return null;
                    }

                    // Reemplaza ; o espacios redundantes por coma única
                    $emails = preg_split('/[;,]+/', $state);

                    // Trim, filtrar vacíos y eliminar duplicados
                    $emails = collect($emails)
                        ->map(fn($email) => trim($email))
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    return implode(',', $emails);
                })

                // 2) Validar con regex: uno o más emails separados por comas
                ->rules([
                    'nullable',
                    // Regex para lista de emails validos separados por comas
                    'regex:/^([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})(?:,([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}))*$/',
                ])

                ->placeholder('ejemplo1@dominio.com, ejemplo2@dominio.com'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->dateTime('d/m/Y h:i a')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->dateTime('d/m/Y h:i a')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activa')
                    ->sortable(),
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('bcc_emails')
                    ->label('BCC')
                    ->limit(30),
                Tables\Columns\TextColumn::make('exams_count')
                    ->label('N° Exámenes')
                    ->counts('exams')
                    ->sortable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExamsRelationManager::class,
            AssignedUsersRelationManager::class,
            ThresholdsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDncs::route('/'),
            'create' => Pages\CreateDnc::route('/create'),
            'edit' => Pages\EditDnc::route('/{record}/edit'),
        ];
    }
}
