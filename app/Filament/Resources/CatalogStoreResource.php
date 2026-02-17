<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogStoreResource\Pages;
use App\Filament\Resources\CatalogStoreResource\RelationManagers\ProfilesRelationManager;
use App\Models\CatalogStore;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class CatalogStoreResource extends Resource
{
    protected static ?string $model = CatalogStore::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Tiendas';
    protected static ?string $modelLabel = 'Tienda';
    protected static ?string $pluralModelLabel = 'Tiendas';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 5;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tienda')
                    ->description('Datos de la tienda')
                    ->schema([
                        Forms\Components\Fieldset::make('Identificación externa')
                            ->schema([
                                Forms\Components\TextInput::make('external_store_id')
                                    ->label('ID externo (ID TDA)')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('external_account_number')
                                    ->label('Cuenta (COMEX)')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('business_name')
                                    ->label('Razón social')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Fieldset::make('Ubicación jerárquica')
                            ->schema([
                                Forms\Components\Select::make('state_id')
                                    ->label('Estado')
                                    ->relationship('state', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('division_id')
                                    ->label('División')
                                    ->relationship('division', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('region_id')
                                    ->label('Región')
                                    ->relationship('region', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('zone_id')
                                    ->label('Territorio')
                                    ->relationship('zone', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\Fieldset::make('Datos de la tienda')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la tienda')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('dealership_id')
                                    ->label('Concesionario')
                                    ->relationship('dealership', 'name')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('address')
                                    ->label('Dirección')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('state.name')
                    ->label('Estado')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('external_store_id')
                    ->label('ID TDA')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('external_account_number')
                    ->label('Cuenta')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('Razón social')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('division.name')
                    ->label('División')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('cyan'),

                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('blue'),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Territorio')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('indigo'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Tienda')
                    ->searchable(),

                Tables\Columns\TextColumn::make('dealership.name')
                    ->label('Concesionario')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->color('green'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('division_id')->label('División')->relationship('division', 'name'),
                SelectFilter::make('region_id')->label('Región')->relationship('region', 'name'),
                SelectFilter::make('zone_id')->label('Territorio')->relationship('zone', 'name'),
                SelectFilter::make('state_id')->label('Estado')->relationship('state', 'name'),
                SelectFilter::make('dealership_id')->label('Concesionario')->relationship('dealership', 'name'),
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
            ProfilesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogStores::route('/'),
            'create' => Pages\CreateCatalogStore::route('/create'),
            'edit' => Pages\EditCatalogStore::route('/{record}/edit'),
        ];
    }
}
