<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DncUserOverrideResource\Pages;
use App\Models\DncUserOverride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DncUserOverrideResource extends Resource
{
    protected static ?string $model = DncUserOverride::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Anulaciones DNC';
    protected static ?string $modelLabel = 'Anulación';
    protected static ?string $pluralModelLabel = 'Anulaciones';
    protected static ?string $navigationGroup = 'DNC';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('dnc_id')
                    ->relationship('dnc', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\DateTimePicker::make('custom_start_date')
                    ->label('Fecha inicio personalizada')
                    ->nullable(),
                    
                Forms\Components\DateTimePicker::make('custom_end_date')
                    ->label('Fecha fin personalizada')
                    ->nullable(),
                    
                Forms\Components\Textarea::make('reason')
                    ->label('Razón')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dnc.name')
                    ->label('DNC')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('custom_start_date')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('custom_end_date')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('reason')
                    ->label('Razón')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('dnc_id')
                    ->relationship('dnc', 'name')
                    ->label('DNC'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDncUserOverrides::route('/'),
            'create' => Pages\CreateDncUserOverride::route('/create'),
            'edit' => Pages\EditDncUserOverride::route('/{record}/edit'),
        ];
    }
}
