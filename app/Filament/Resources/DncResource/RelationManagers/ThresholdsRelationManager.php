<?php

namespace App\Filament\Resources\DncResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;

class ThresholdsRelationManager extends RelationManager
{
    protected static string $relationship = 'thresholds';
    protected static ?string $recordTitleAttribute = 'days_before';
    protected static ?string $title = 'Notificaciones';
    protected static ?string $modelLabel = 'Notificación';
    protected static ?string $pluralModelLabel = 'Notificaciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('days_before')
                    ->label('Días antes')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn () => $this->ownerRecord->getMaxNotificationRange())
                    ->required()
                    ->helperText(fn () =>
                        'Valor entre 0 y ' .
                        $this->ownerRecord->getMaxNotificationRange() .
                        ' días (periodo DNC).'
                    ),
            ]);
    }

    public function table(Table $table): Table
    {
        $today     = Carbon::now()->startOfDay();
        $endDate   = Carbon::parse($this->getOwnerRecord()->end_date)->endOfDay();

        return $table
            ->columns([
                // Umbral: "5 días antes"
                TextColumn::make('days_before')
                    ->label('Umbral')
                    ->formatStateUsing(fn (int $state): string => "{$state} días antes"),

                // Fecha de envío
                TextColumn::make('notify_date')
                    ->label('Fecha de envío')
                    ->getStateUsing(fn ($record) =>
                        Carbon::now()
                              ->startOfDay()
                              ->addDays($record->days_before)
                              ->format('d/m/Y')
                    ),

                // Estado considerando end_date de la DNC
                TextColumn::make('estado')
                    ->label('Estado')
                    ->getStateUsing(function ($record) use ($today, $endDate) {
                        $notifyDate = Carbon::now()
                                            ->startOfDay()
                                            ->addDays($record->days_before);
                        if ($notifyDate->greaterThan($endDate)) {
                            return 'Fuera de rango';
                        }
                        if ($notifyDate->lte($today)) {
                            return 'Vencido';
                        }
                        return 'Activo';
                    })
                    ->badge()
                    ->colors([
                        'success'      => fn ($state): bool => $state === 'Activo',
                        'warning'      => fn ($state): bool => $state === 'Fuera de rango',
                        'danger'       => fn ($state): bool => $state === 'Vencido',
                    ]),
            ])
            ->defaultSort('days_before', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
