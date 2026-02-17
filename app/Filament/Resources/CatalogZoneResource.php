<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CatalogZone;
use Filament\Resources\Resource;
use App\Filament\Resources\CatalogZoneResource\Pages;
use App\Filament\Resources\CatalogZoneResource\RelationManagers\DealershipsRelationManager;

class CatalogZoneResource extends Resource
{
    protected static ?string $model = CatalogZone::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Territorios';
    protected static ?string $modelLabel = 'Territorio';
    protected static ?string $pluralModelLabel = 'Territorios';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('region_id')
                    ->label('Región')
                    ->relationship('region', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre del territorio')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable()
                    ->toggleable()
                    ->url(fn () => null),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del territorio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
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
            DealershipsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogZones::route('/'),
            'create' => Pages\CreateCatalogZone::route('/create'),
            'edit' => Pages\EditCatalogZone::route('/{record}/edit'),
        ];
    }
}
