<?php

namespace App\Filament\Resources;

use App\Models\CatalogRegion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\CatalogRegionResource\Pages;
use App\Filament\Resources\CatalogRegionResource\RelationManagers\ZonesRelationManager;

class CatalogRegionResource extends Resource
{
    protected static ?string $model = CatalogRegion::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Regiones';
    protected static ?string $modelLabel = 'Región';
    protected static ?string $pluralModelLabel = 'Regiones';
    protected static ?int $navigationSort = 2;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('division_id')
                    ->label('División')
                    ->relationship('division', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la región')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('division.name')
                    ->label('División')
                    ->sortable()
                    ->toggleable()
                    ->url(fn () => null),

                Tables\Columns\TextColumn::make('name')
                    ->label('Región')
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
            ZonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogRegions::route('/'),
            'create' => Pages\CreateCatalogRegion::route('/create'),
            'edit' => Pages\EditCatalogRegion::route('/{record}/edit'),
        ];
    }
}
