<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\UserProfile;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;

class UserProfileImporter extends Importer
{
    protected static ?string $model = UserProfile::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'exists:users,email']),
        ];
    }

    public function resolveRecord(): ?Model
    {
        $user = User::where('email', $this->data['email'])->first();

        if (! $user || ! $user->hasRole('participante')) {
            return null;
        }

        return UserProfile::firstOrNew([
            'user_id' => $user->id,
        ]);
    }

    public function fillRecord(): void
    {
        $user = User::where('email', $this->data['email'])->first();

        if (! $user || ! isset($this->options['store_id'])) {
            return;
        }

        // Carga la tienda y sus relaciones jerárquicas
        $store = \App\Models\CatalogStore::find($this->options['store_id']);

        $this->record->user_id       = $user->id;
        $this->record->store_id      = $store->id;
        $this->record->division_id   = $store->division_id;
        $this->record->region_id     = $store->region_id;
        $this->record->zone_id       = $store->zone_id;
        $this->record->dealership_id = $store->dealership_id;
        $this->record->state_id      = $store->state_id;
        $this->record->city_id       = $store->city_id;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de perfiles fue completada. ' .
            number_format($import->successful_rows) . ' ' . str('registro')->plural($import->successful_rows) . ' importado(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' falló/fallaron al importar.';
        }

        return $body;
    }
}
