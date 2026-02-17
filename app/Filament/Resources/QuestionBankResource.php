<?php

namespace App\Filament\Resources;

use App\Filament\Imports\QuestionsToBankImporter;
use App\Models\QuestionBank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\QuestionBankResource\Pages;
use App\Filament\Resources\QuestionBankResource\Actions\ImportQuestionsAction;
use App\Filament\Resources\QuestionBankResource\RelationManagers\QuestionsRelationManager;

class QuestionBankResource extends Resource
{
    protected static ?string $model = QuestionBank::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Bancos de Preguntas';
    protected static ?string $modelLabel = 'Banco de Preguntas';
    protected static ?string $pluralModelLabel = 'Bancos de Preguntas';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationGroup = 'Recursos'; 

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre del banco')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('parent_id')
                ->label('Banco padre (opcional)')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload(),

            Forms\Components\Select::make('exam_id')
                ->label('Examen asociado (opcional)')
                ->relationship('exam', 'name')
                ->searchable()
                ->preload()
                ->helperText('Si se asigna, este banco será específico para un examen.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Banco padre'),
                Tables\Columns\TextColumn::make('exam.name')
                    ->label('Examen')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('exam_id')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? 'Específico' : 'General')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_id')
                    ->label('Filtrar por examen')
                    ->relationship('exam', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionBanks::route('/'),
            'create' => Pages\CreateQuestionBank::route('/create'),
            'edit' => Pages\EditQuestionBank::route('/{record}/edit'),
        ];
    }
}
