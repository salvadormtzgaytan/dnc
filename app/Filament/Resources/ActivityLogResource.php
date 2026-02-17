<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationGroup = 'Auditoría';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Bitácoras';
    protected static ?string $modelLabel = 'Bitácora';
    protected static ?string $pluralModelLabel = 'Bitácoras';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')->label('Módulo'),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID afectado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Acción'),

                // ✅ Modificado para evitar error al ordenar relación polimórfica
                Tables\Columns\TextColumn::make('causer.email')
                    ->label('Hecho por')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->causer?->email ?? 'N/A';
                    }),

                Tables\Columns\TextColumn::make('properties')
                    ->label('Cambios')
                    ->formatStateUsing(function ($state, $record) {
                        $changes = $record->changes();

                        if (!isset($changes['attributes'])) {
                            return 'Sin cambios detectados';
                        }

                        $formattedChanges = [];
                        foreach ($changes['attributes'] as $key => $newValue) {
                            if ($key === 'updated_at') {
                                continue;
                            }

                            $oldValue = $changes['old'][$key] ?? 'N/A';

                            if ($key === 'is_active') {
                                $formattedChanges[] = sprintf(
                                    '%s: %s → %s',
                                    'Estado',
                                    $oldValue ? 'Activo' : 'Inactivo',
                                    $newValue ? 'Activo' : 'Inactivo'
                                );
                            } else {
                                $formattedChanges[] = sprintf(
                                    '%s: "%s" → "%s"',
                                    $key,
                                    static::formatChangeValue($oldValue),
                                    static::formatChangeValue($newValue)
                                );
                            }
                        }

                        return $formattedChanges ? implode('<br>', $formattedChanges) : 'Cambios no relevantes';
                    })
                    ->html()
                    ->wrap()
                    ->limit(1000),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function formatChangeValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
