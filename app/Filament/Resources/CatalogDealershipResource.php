<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogDealershipResource\Pages;
use App\Filament\Resources\CatalogDealershipResource\RelationManagers\StoresRelationManager;
use App\Models\CatalogDealership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CatalogDealershipResource extends Resource
{
    protected static ?string $model = CatalogDealership::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';
    protected static ?string $navigationLabel = 'Concesionarias';
    protected static ?string $modelLabel = 'Concesionaria';
    protected static ?string $pluralModelLabel = 'Concesionarias';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('zone_id')
                    ->label('Territorio')
                    ->relationship('zone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la concesionaria')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Territorio')
                    ->sortable()
                    ->toggleable()
                    ->url(fn () => null),

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
            StoresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogDealerships::route('/'),
            'create' => Pages\CreateCatalogDealership::route('/create'),
            'edit' => Pages\EditCatalogDealership::route('/{record}/edit'),
        ];
    }
}
