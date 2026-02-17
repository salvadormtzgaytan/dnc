<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CatalogDivision;
use Filament\Resources\Resource;
use App\Filament\Resources\CatalogDivisionResource\Pages;
use App\Filament\Resources\CatalogDivisionResource\RelationManagers\RegionsRelationManager;

class CatalogDivisionResource extends Resource
{
    protected static ?string $model = CatalogDivision::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Divisiones';
    protected static ?string $modelLabel = 'División';
    protected static ?string $pluralModelLabel = 'Divisiones';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la división')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
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
           RegionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogDivisions::route('/'),
            'create' => Pages\CreateCatalogDivision::route('/create'),
            'edit' => Pages\EditCatalogDivision::route('/{record}/edit'),
        ];
    }
}
