<?php

namespace App\Filament\Resources\DncResource\RelationManagers;

use App\Models\User;
use App\Models\UserGroup;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ImportAction;
use App\Filament\Imports\DncUserAssignmentImporter;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * RelationManager para la asignación de usuarios a una DNC.
 */
class AssignedUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'assignedUsers';
    protected static ?string $title = 'Asignaciones';
    protected static ?string $label = 'Asignación';
    protected static ?string $pluralLabel = 'Asignaciones';

    /**
     * Formulario para asignar un solo usuario (usado en acciones por fila, si aplica).
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Seleccionar usuario')
                ->options(User::query()
                    ->select('id', 'name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required(),
        ]);
    }

    /**
     * Configuración de la tabla principal.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre del usuario'),
                Tables\Columns\TextColumn::make('email')->label('Correo electrónico'),
            ])
            ->headerActions([
                $this->assignMultipleUsersAction(),
                $this->assignByGroupAction(),
                $this->importFromExcelAction(),
            ])
            ->emptyStateHeading('No hay usuarios asignados')
            ->emptyStateDescription('Usa el botón "Asignar usuario" para agregar personas a esta DNC.')
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Quitar asignación')
                    ->action(function ($record, $livewire) {
                        // $livewire->getOwnerRecord() es la DNC actual
                        // $record es el usuario asignado
                        $dnc = $livewire->getOwnerRecord();
                        $dnc->assignedUsers()->detach($record->id);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Quitar asignaciones'),
                ]),
            ]);
    }

    /**
     * Acción para asignar múltiples usuarios de forma manual.
     */
    protected function assignMultipleUsersAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('assignMultipleUsers')
            ->label('Asignar usuarios')
            ->icon('heroicon-o-user-plus')
            ->form([
                Forms\Components\Select::make('user_ids')
                    ->label('Seleccionar usuarios')
                    ->options(User::query()
                        ->select('id', 'name', 'email')
                        ->get()
                        ->mapWithKeys(fn($user) => [
                            $user->id => "{$user->name} ({$user->email})"
                        ]))
                    ->searchable()
                    ->multiple()
                    ->required(),
            ])
            ->action(function (array $data) {
                $dnc = $this->getOwnerRecord();
                $ids = collect($data['user_ids'])->filter();

                $alreadyAssigned = $dnc->assignedUsers()->pluck('user_id');
                $newAssignments = $ids->diff($alreadyAssigned);

                if ($newAssignments->isEmpty()) {
                    Notification::make()
                        ->title('Todos los usuarios seleccionados ya están asignados.')
                        ->warning()
                        ->send();
                    return;
                }

                $dnc->assignedUsers()->attach($newAssignments);

                Notification::make()
                    ->title("Se asignaron {$newAssignments->count()} usuario(s) correctamente.")
                    ->success()
                    ->send();
            })
            ->modalHeading('Asignar múltiples usuarios a esta DNC')
            ->modalSubmitActionLabel('Asignar')
            ->modalWidth('lg');
    }

    /**
     * Acción para asignar usuarios a partir de un grupo.
     */
    protected function assignByGroupAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('assignByGroup')
            ->label('Asignar por grupo')
            ->icon('heroicon-o-users')
            ->visible(UserGroup::count() > 0)
            ->form(function () {
                $groups = UserGroup::orderBy('name')->pluck('name', 'id');

                return $groups->isEmpty()
                    ? [
                        Forms\Components\Placeholder::make('no_groups')
                            ->label('Sin grupos disponibles')
                            ->content('No hay grupos de usuarios registrados.')
                    ]
                    : [
                        Forms\Components\Select::make('user_group_id')
                            ->label('Grupo de usuarios')
                            ->options($groups)
                            ->required()
                            ->searchable()
                    ];
            })
            ->action(function (array $data) {
                $dnc = $this->getOwnerRecord();
                $group = UserGroup::with('users')->find($data['user_group_id']);
                $users = $group?->users ?? collect();

                $ids = $users->pluck('id');
                $alreadyAssigned = $dnc->assignedUsers()->pluck('user_id');
                $newAssignments = $ids->diff($alreadyAssigned);

                if ($newAssignments->isEmpty()) {
                    Notification::make()
                        ->title("Todos los usuarios del grupo '{$group->name}' ya estaban asignados.")
                        ->warning()
                        ->send();
                    return;
                }

                $dnc->assignedUsers()->attach($newAssignments);

                Notification::make()
                    ->title("Se asignaron {$newAssignments->count()} usuarios del grupo '{$group->name}'")
                    ->success()
                    ->send();
            });
    }

    /**
     * Acción para importar usuarios desde archivo Excel.
     */
    protected function importFromExcelAction(): ImportAction
    {
        return ImportAction::make()
            ->label('Importar desde Excel')
            ->importer(DncUserAssignmentImporter::class)
            ->options([ // ¡Usa options() en lugar de arguments()!
                'dnc_id' => $this->getOwnerRecord()->id,
            ]);
    }
}
