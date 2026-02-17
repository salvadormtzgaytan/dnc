<?php

namespace App\Filament\Resources\CatalogStoreResource\RelationManagers;

use App\Filament\Imports\UserProfileImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Table;

class ProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'profiles';

    protected static ?string $title = 'usuarios asignados';
    protected static ?string $label = 'Participante';
    protected static ?string $pluralLabel = 'usuarios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->role('participante')
                            ->whereDoesntHave('profile', function ($q) {
                                $q->whereNotNull('store_id');
                            })
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} - {$record->email}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText(function () {
                        $availableCount = \App\Models\User::role('participante')
                            ->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('store_id'))
                            ->count();

                        return $availableCount === 0
                            ? 'No hay usuarios disponibles para asignar.'
                            : 'Selecciona un participante disponible.';
                    })

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nombre'),
                Tables\Columns\TextColumn::make('user.email')->label('Correo'),
                Tables\Columns\TextColumn::make('created_at')->label('Asignado el')->dateTime()->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar usuario')
                    ->modalHeading('Asignar usuario a tienda')
                    ->modalSubmitActionLabel('Asignar')
                    ->visible(function () {
                        return \App\Models\User::role('participante')
                            ->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('store_id'))
                            ->exists();
                    })
                    ->mutateFormDataUsing(function (array $data, $livewire) {
                        $store = $livewire->ownerRecord;

                        return array_merge($data, [
                            'store_id' => $store->id,
                            'division_id' => $store->division_id,
                            'region_id' => $store->region_id,
                            'zone_id' => $store->zone_id,
                            'dealership_id' => $store->dealership_id,
                            'state_id' => $store->state_id,
                            'city_id' => $store->city_id,
                        ]);
                    }),
                ImportAction::make()
                    ->importer(UserProfileImporter::class)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->label('Asignar usuarios a tienda')
                    ->modalHeading('Asignar usuarios')
                    ->options(fn ($livewire) => [
                        'store_id' => $livewire->ownerRecord->id,
                    ]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading(function () {
                return \App\Models\User::role('participante')
                    ->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('store_id'))
                    ->exists()
                    ? 'Sin usuarios asignados'
                    : 'No hay usuarios disponibles';
            })
            ->emptyStateDescription(function () {
                return \App\Models\User::role('participante')
                    ->whereDoesntHave('profile', fn ($q) => $q->whereNotNull('store_id'))
                    ->exists()
                    ? 'Haz clic en "Asignar usuario" para agregar uno.'
                    : 'Todos los usuarios con rol participante ya han sido asignados a tiendas.';
            });
    }
}
