<?php

// app/Filament/Resources/QuestionResource.php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Question;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CatalogLevel;
use App\Models\CatalogSegment;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers\ChoicesRelationManager;

class QuestionResource extends Resource
{
    protected static ?string $model            = Question::class;
    protected static ?string $navigationIcon   = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel  = 'Preguntas';
    protected static ?string $modelLabel       = 'Pregunta';
    protected static ?string $pluralModelLabel = 'Preguntas';
    protected static ?string $navigationGroup  = 'Recursos';
    protected static ?int $navigationSort      = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('question_bank_id')
                ->label('Banco de Preguntas')
                ->relationship('questionBank', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->default(fn ($record) => $record?->question_bank_id ?? request()->get('question_bank_id'))
                ->disabled(fn ($record) => $record?->question_bank_id || request()->has('question_bank_id'))
                ->options(\App\Models\QuestionBank::pluck('name', 'id'))
                ->helperText('Si no aparece ningún banco, crea uno primero desde el módulo de Bancos.'),

            Forms\Components\Select::make('catalog_segment_id')
                ->label('Segmento')
                ->relationship('segment', 'name')
                ->searchable()
                ->nullable()
                ->default(fn () => CatalogSegment::where('name', 'Sin Segmento')->value('id')),

            Forms\Components\Select::make('catalog_level_id')
                ->label('Nivel')
                ->relationship('level', 'name')
                ->searchable()
                ->nullable()
                ->default(fn () => CatalogLevel::where('name', 'Sin Nivel')->value('id')),

            Forms\Components\TextInput::make('title')
                ->label('Nombre descriptivo')
                ->required()
                ->maxLength(255),

            Forms\Components\RichEditor::make('text')
                ->label('Texto de la pregunta')
                ->required()
                ->columnSpanFull(),

            Forms\Components\Select::make('type')
                ->label('Tipo de pregunta')
                ->options(Question::TYPES)
                ->required(),

            Forms\Components\TextInput::make('default_score')
                ->label('Puntaje por defecto')
                ->numeric()
                ->default(1)
                ->required(),

            Forms\Components\Toggle::make('shuffle_choices')
                ->label('¿Reordenar respuestas aleatoriamente?')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('questionBank.name')->label('Banco'),
                Tables\Columns\TextColumn::make('segment.name')->label('Segmento'), // Nuevo
                Tables\Columns\TextColumn::make('level.name')->label('Nivel'), // Nuevo
                Tables\Columns\TextColumn::make('type')->label('Tipo'),
                Tables\Columns\TextColumn::make('default_score')->label('Puntaje'),
                Tables\Columns\IconColumn::make('shuffle_choices')
                    ->boolean()
                    ->label('Respuestas aleatorias'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Creado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('catalog_segment_id')
                    ->relationship('segment', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('catalog_level_id')
                    ->relationship('level', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChoicesRelationManager::class,
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(request()->has('search'), function (Builder $query) {
                $search = request('search');
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                          ->orWhere('text', 'like', "%{$search}%");
                });
            });
    }
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }

}
