<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogPositionResource\Pages;
use App\Models\CatalogPosition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CatalogPositionResource extends Resource
{
    protected static ?string $model = CatalogPosition::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase'; // Ícono sugerido
    protected static ?string $navigationLabel = 'Puestos';
    protected static ?string $modelLabel = 'Puesto';
    protected static ?string $pluralModelLabel = 'Puestos';
    protected static ?string $navigationGroup = 'Catálogos'; // Agrupar bajo 'Catálogos'
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del puesto')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del puesto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogPositions::route('/'),
            'create' => Pages\CreateCatalogPosition::route('/create'),
            'edit' => Pages\EditCatalogPosition::route('/{record}/edit'),
        ];
    }
}
