<?php

namespace App\Filament\Resources\ExamResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class GroupOverridesRelationManager extends RelationManager
{
    protected static string $relationship = 'groupOverrides';
    protected static ?string $title = 'Anulaciones por Grupo';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('start_at')
                ->label('Fecha de apertura personalizada')
                ->nullable(),
            Forms\Components\DateTimePicker::make('end_at')
                ->label('Fecha de cierre personalizada')
                ->nullable(),
            Forms\Components\TextInput::make('max_attempts')
                ->label('Intentos permitidos')
                ->numeric()
                ->minValue(1)
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('group.name')->label('Grupo'),
            Tables\Columns\TextColumn::make('start_at')->label('Fecha de apertura')->dateTime(),
            Tables\Columns\TextColumn::make('end_at')->label('Fecha de cierre')->dateTime(),
            Tables\Columns\TextColumn::make('max_attempts')->label('Intentos'),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->emptyStateHeading('No hay anulaciones por grupo registradas');
    }
}
