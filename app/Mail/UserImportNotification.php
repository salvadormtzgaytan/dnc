<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Filament\Actions\Imports\Models\Import;

class UserImportNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $importedCount,
        public int $failedCount,
        public string $csvPath,
        public ?Collection $sampleUsers = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Resumen de importación: {$this->importedCount} usuarios"
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user_import',
            with: [
                'importedCount' => $this->importedCount,
                'failedCount' => $this->failedCount,
                'sampleUsers' => $this->sampleUsers,
            ],
        );
    }

    public function attachments(): array
    {
        return $this->importedCount > 0
            ? [
                Attachment::fromStorageDisk('local', $this->csvPath)
                    ->as('usuarios_importados_' . now()->format('Y-m-d') . '.csv')
                    ->withMime('text/csv'),
            ]
            : [];
    }

    /**
     * Genera el archivo CSV con usuarios importados y fallidos.
     */
    public static function generateCsvFile(string $filePath, Collection $userIds, Collection $passwords, Import $import): void
    {
        Log::channel('syslog')->debug('✔️ Ingreso a crear el cvs');
        $storage = Storage::disk('local');
        $includePassword = $passwords->isNotEmpty();

        $storage->put($filePath, self::getCsvHeaders($includePassword, true));

        $failedRows = collect($import->getFailedRows());
        $errorsByEmail = $failedRows->mapWithKeys(function ($row) {
            return [$row['data']['email'] ?? uniqid('error_') => implode('; ', $row['errors'])];
        });

        // Exportar usuarios exitosos
        User::whereIn('id', $userIds)
            ->with(['profile.position', 'profile.store'])
            ->chunk(500, function ($users) use ($storage, $filePath, $passwords, $errorsByEmail) {
                $csvLines = $users->map(function ($user) use ($passwords, $errorsByEmail) {
                    $fields = [
                        '"' . str_replace('"', '""', $user->name) . '"',
                        $user->email,
                        $user->profile->position->name ?? 'N/A',
                        $user->profile->store->store_name ?? 'N/A',
                        $user->created_at->toDateTimeString(),
                    ];

                    if ($passwords->has($user->email)) {
                        $fields[] = $passwords[$user->email];
                    }

                    // Usuarios exitosos no tienen error
                    $fields[] = '';

                    return implode(',', $fields);
                })->implode("\n");

                $storage->append($filePath, $csvLines);
            });

        // Exportar filas fallidas (solo en CSV, no en base de datos)
        foreach ($errorsByEmail as $email => $errorMessage) {
            $fields = [
                '""',
                $email,
                '',
                '',
                '',
            ];

            if ($includePassword) {
                $fields[] = '';
            }

            $fields[] = $errorMessage;

            $storage->append($filePath, implode(',', $fields));
        }
    }

    private static function getCsvHeaders(bool $includePassword = false, bool $includeError = false): string
    {
        $headers = [
            '"Nombre"',
            '"Email"',
            '"Puesto"',
            '"Tienda"',
            '"Fecha Creación"',
        ];

        if ($includePassword) {
            $headers[] = '"Contraseña Generada"';
        }

        if ($includeError) {
            $headers[] = '"Motivo de error"';
        }

        return implode(',', $headers) . "\n";
    }

    public static function getSampleUsers(Collection $userIds): Collection
    {
        return User::whereIn('id', $userIds->take(5))
            ->with(['profile.position', 'profile.store'])
            ->get();
    }
}
