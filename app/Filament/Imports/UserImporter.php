<?php

namespace App\Filament\Imports;

use App\Mail\GenericInfoNotification;
use App\Mail\UserCreatedMail;
use App\Mail\UserImportNotification;
use App\Models\CatalogDealership;
use App\Models\CatalogPosition;
use App\Models\CatalogStore;
use App\Models\User;
use App\Utils\PasswordGenerator;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    protected static int $chunkSize = 100;

    public static function getColumns(): array
    {
        return [
          ImportColumn::make('name')
            ->label('Nombre')
            ->requiredMapping()
            ->rules(['required', 'max:255']),

          ImportColumn::make('email')
            ->label('Correo electrónico')
            ->requiredMapping()
            ->rules(['required', 'email', 'max:255', 'unique:users,email']),
          ImportColumn::make('rfc')
              ->label('RFC')
              ->rules([
                  'nullable',
              ])
              ->helperText('Ingresa el RFC asignado por el SAT'),
          ImportColumn::make('is_active')
            ->label('¿Activo?')
            ->boolean()
            ->rules(['nullable', 'boolean']),

          ImportColumn::make('password')
            ->label('Contraseña')
            ->rules([
              'nullable',
              'min:8',
              'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ])
            ->helperText('Debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo'),

          ImportColumn::make('external_store_id')
            ->label('ID TDA (ID de Tienda)')
            ->rules(['nullable', 'numeric', 'exists:catalog_stores,external_store_id'])
            ->fillRecordUsing(fn ($record, $state) => null),

          ImportColumn::make('dealership_name')
            ->label('Nombre de la concesionaria')
            ->rules(['nullable', 'string'])
            ->fillRecordUsing(fn ($record, $state) => null),

          ImportColumn::make('position_name')
            ->label('Nombre del puesto')
            ->rules(['nullable', 'string'])
            ->fillRecordUsing(fn ($record, $state) => null),
        ];
    }

    protected function beforeValidate(): void
    {
        $this->validateEmail();
        $this->validateStoreAndDealership();
        $this->validatePosition();
    }

    private function getCacheKey(string $suffix): string
    {
        return "import_{$this->import->id}_{$suffix}";
    }

    private function addToCache(string $key, $value): void
    {
        $data = Cache::get($key, []);
        $data[] = $value;
        Cache::put($key, $data, now()->addHours(24));
    }

    private function logError(string $error): void
    {
        $this->addToCache($this->getCacheKey('errors'), $error);
    }


    private function validateEmail(): void
    {
        $processedCount = $this->import->processed_rows;
        $currentRow     = $processedCount + 1;
        if (empty($this->data['email'])) {
            $error = "Fila {$currentRow}: El campo email es requerido.";
            $this->logError($error);
            throw new RowImportFailedException('El campo email es requerido.');
        }

        if (!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Fila {$currentRow}: El formato del email no es válido.";
            $this->logError($error);
            throw new RowImportFailedException('El formato del email no es válido.');
        }

        if (User::where('email', $this->data['email'])->exists()) {
            $error = "El email {$this->data['email']} ya está registrado.";
            $this->logError($error);
            throw new RowImportFailedException("El email {$this->data['email']} ya está registrado.");
        }
    }

    private function validateStoreAndDealership(): void
    {
        $processedCount = $this->import->processed_rows;
        $currentRow     = $processedCount + 1;

        $externalStoreId = $this->data['external_store_id'] ?? null;
        $dealershipName  = $this->data['dealership_name']   ?? null;

        if ($externalStoreId && !CatalogStore::where('external_store_id', $externalStoreId)->exists()) {
            $error = "Fila {$currentRow}: La tienda con ID TDA {$externalStoreId} no existe.";
            $this->logError($error);
            throw new RowImportFailedException("La tienda con ID TDA {$externalStoreId} no existe.");
        }

        if (!$externalStoreId && $dealershipName && !CatalogDealership::where('name', $dealershipName)->exists()) {
            $error = "Fila {$currentRow}: La concesionaria {$dealershipName} no existe.";
            $this->logError($error);
            throw new RowImportFailedException("La concesionaria {$dealershipName} no existe.");
        }
    }

    private function validatePosition(): void
    {
        $processedCount = $this->import->processed_rows;
        $currentRow     = $processedCount + 1;
        if (
            !empty($this->data['position_name']) && !CatalogPosition::where('name', $this->data['position_name'])->exists()
        ) {
            $error = "Fila {$currentRow}: El puesto {$this->data['position_name']} no existe.";
            $this->logError($error);
            throw new RowImportFailedException("El puesto {$this->data['position_name']} no existe.");
        }
    }

    public function resolveRecord(): ?User
    {
        $generatePassword = $this->options['generate_password'] ?? false;

        $password = $generatePassword
          ? PasswordGenerator::generate()
          : ($this->data['password'] ?? PasswordGenerator::generate());

        Cache::put($this->getCacheKey("password_{$this->data['email']}"), $password, now()->addHours(24));

        // Solo los campos que existen en la tabla users
        return new User([
          'name'      => $this->data['name'],
          'email'     => $this->data['email'],
          'rfc'       => $this->data['rfc'] ?? null,
          'password'  => $password, // hashed automáticamente
          'is_active' => filter_var($this->data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function afterCreate(): void
    {
        $store = !empty($this->data['external_store_id'])
          ? CatalogStore::where('external_store_id', $this->data['external_store_id'])->first()
          : null;

        $dealershipId = $store?->dealership_id;

        if (!$store && !empty($this->data['dealership_name'])) {
            $dealership   = CatalogDealership::where('name', $this->data['dealership_name'])->first();
            $dealershipId = $dealership?->id;
        }

        $position = !empty($this->data['position_name'])
          ? CatalogPosition::where('name', $this->data['position_name'])->first()
          : null;

        // 🧠 Intentar obtener zone_id desde la concesionaria si existe
        $zoneId = $dealership?->zone_id ?? null;

        DB::transaction(function () use ($store, $position, $dealershipId, $zoneId) {
            $this->record->profile()->create([
              'store_id'      => $store?->id,
              'position_id'   => $position?->id,
              'dealership_id' => $dealershipId,
              'zone_id'       => $zoneId,
            ]);
            
            $this->record->assignRole('participante');
        });

        $password = Cache::get($this->getCacheKey("password_{$this->record->email}"));
        
        if ($this->options['send_email'] ?? false && $password) {
            try {
                Mail::to($this->record->email)
                  ->bcc(env('MAIL_ADMIN_ADDRESS', 'admin@example.com')) 
                  ->queue(new UserCreatedMail($this->record, $password));
            } catch (\Exception $e) {
                Log::error("Error enviando correo a {$this->record->email}: {$e->getMessage()}");
            }
        }
        
        $this->addToCache($this->getCacheKey('users'), [
            'id' => $this->record->id,
            'email' => $this->record->email,
        ]);
    }


    protected function afterComplete(): void
    {
        $importedUsers = Cache::get($this->getCacheKey('users'), []);
        $errors = Cache::get($this->getCacheKey('errors'), []);

        if (empty($importedUsers)) {
            Cache::forget($this->getCacheKey('errors'));
            return;
        }

        $userIds = collect($importedUsers)->pluck('id');
        $passwords = collect($importedUsers)->mapWithKeys(function ($user) {
            return [$user['email'] => Cache::get($this->getCacheKey("password_{$user['email']}"))];
        });

        $csvPath = 'temp/usuarios_' . now()->timestamp . '.csv';

        UserImportNotification::generateCsvFile(
            $csvPath,
            $userIds,
            $passwords,
            $this->import
        );

        $sampleUsers = UserImportNotification::getSampleUsers($userIds);

        Mail::to($this->import->user)
          ->queue(new UserImportNotification(
              importedCount: $this->import->successful_rows,
              failedCount: $this->import->getFailedRowsCount(),
              csvPath: $csvPath,
              sampleUsers: $sampleUsers
          ));

        Cache::forget($this->getCacheKey('users'));
        Cache::forget($this->getCacheKey('errors'));
        collect($importedUsers)->each(fn($user) => 
            Cache::forget($this->getCacheKey("password_{$user['email']}"))
        );
    }

    public static function getOptionsFormComponents(): array
    {
        return [
          Toggle::make('generate_password')
            ->label('¿Generar contraseña automáticamente?')
            ->helperText('Si se activa, se ignorará la columna "password" del archivo y se generará una clave segura.')
            ->default(false),

          Toggle::make('send_email')
            ->label('¿Enviar correo de bienvenida?')
            ->helperText('Se enviará un correo a cada usuario importado con sus credenciales.')
            ->default(true),
        ];
    }



    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de usuarios finalizó: ' . number_format($import->successful_rows) . ' ' . str('registro')->plural($import->successful_rows) . ' importado(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' fallido(s).';
        }

        return $body;
    }

    private function validateRfc(): void
    {
        $processedCount = $this->import->processed_rows;
        $currentRow     = $processedCount + 1;

        if (isset($this->data['rfc'])) {
            if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i', $this->data['rfc'])) {
                $error = "Fila {$currentRow}: El RFC {$this->data['rfc']} no tiene un formato válido.";
                $this->logError($error);
                throw new RowImportFailedException('El RFC no tiene un formato válido.');
            }
        }
    }
}
