<?php

namespace App\Filament\Imports;

use App\Models\Dnc;
use App\Models\User;
use App\Models\DncUserAssignment;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Database\Eloquent\Model;

class DncUserAssignmentImporter extends Importer
{
    protected static ?string $model = DncUserAssignment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('user_email')
                ->label('Correo del usuario')
                ->requiredMapping()
                ->rules(['required', 'email'])
                ->relationship(
                    name: 'user',
                    resolveUsing: fn(?string $state): ?User =>
                    User::where('email', trim($state ?? ''))->first()
                ),
        ];
    }

    /**
     * Asigna automáticamente el dnc_id desde el argumento 'dnc' del ImportAction.
     */
    public function resolveRecord(): ?DncUserAssignment
    {
        $dncId = $this->options['dnc_id'] ?? null;
        $userEmail = $this->data['user_email'] ?? null;

        // Validaciones básicas
        if (!$dncId || !$userEmail) {
            return null; // O lanza una excepción
        }

        // Busca el usuario por email
        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            throw new RowImportFailedException('El usuario con email ' . $userEmail . ' no existe.');
        }

        // Verifica si ya existe la asignación (DNC + User)
        $exists = DncUserAssignment::where('dnc_id', $dncId)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return null; // Retorna null para omitir el registro duplicado
        }

        // Crea la asignación solo si no existe
        return new DncUserAssignment(['dnc_id' => $dncId]);
    }

    /**
     * Asigna el user_id y valida que el usuario exista.
     */
    public function hydrateRecord(Model $record, array $data): Model
    {
        $user = User::where('email', $data['user_email'])->first();
        $record->user_id = $user->id;
        return $record;
    }


    public static function getCompletedNotificationBody(Import $import): string
    {
        $count = $import->successful_rows;
        $body  = "Importación completada: {$count} asignación(es) exitosa(s).";

        if ($fails = $import->getFailedRowsCount()) {
            $body .= " {$fails} fila(s) fallidas.";
        }

        return $body;
    }
}
