<?php

namespace App\Console\Commands;

use App\Filament\Imports\UserImporter;
use App\Models\User;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportUsers extends Command
{
    protected $signature = 'users:import {file?} {--generate-password} {--send-email} {--user-id=}';
    protected $description = 'Importar usuarios desde un archivo CSV con validaciones completas';

    public function handle()
    {
        $filePath = $this->argument('file') ?? app_path('Console/user-importer-example.csv');

        if (!file_exists($filePath)) {
            $this->error("El archivo {$filePath} no existe.");
            return 1;
        }

        $this->info("📁 Archivo: {$filePath}");
        $this->newLine();

        $this->info('📥 Iniciando importación de usuarios...');

        $fileName = basename($filePath);
        $storagePath = 'imports/' . $fileName;
        Storage::put($storagePath, file_get_contents($filePath));

        $csv = \League\Csv\Reader::createFromPath(storage_path('app/' . $storagePath));
        $csv->setHeaderOffset(0);
        $totalRows = iterator_count($csv);

        // Determinar el usuario asociado al import
        $userId = $this->option('user-id');
        if ($userId) {
            $userId = (int) $userId;
            $user = User::find($userId);
            if (! $user) {
                $count = User::count();
                $ids = User::pluck('id')->take(5)->implode(', ');
                $this->error("Usuario con id {$userId} no encontrado. Usuarios en DB: {$count}. IDs: {$ids}. Conexión BD: " . config('database.default'));
                Storage::delete($storagePath);
                return 1;
            }
        } else {
            $user = User::first();
            if (! $user) {
                $this->error('No hay usuarios en la base de datos. Pasa --user-id para indicar el usuario asociado.');
                Storage::delete($storagePath);
                return 1;
            }
        }

        $import = Import::create([
            'user_id' => $user->id,
            'file_name' => $fileName,
            'file_path' => $storagePath,
            'importer' => UserImporter::class,
            'total_rows' => $totalRows,
        ]);

        $options = [
            'generate_password' => $this->option('generate-password'),
            'send_email' => $this->option('send-email'),
        ];

        $this->info("📊 Procesando archivo...");
        $this->newLine();

        // Construir columnMap automáticamente usando las columnas del importer y los headers del CSV
        $headers = $csv->getHeader();

        $columnMap = [];
        foreach (UserImporter::getColumns() as $column) {
            $name = $column->getName();

            $found = null;

            foreach ($headers as $header) {
                $normalizedHeader = Str::lower(trim($header));

                if (in_array($normalizedHeader, $column->getGuesses(), true)) {
                    $found = $header;
                    break;
                }
            }

            if ($found) {
                $columnMap[$name] = $found;
                continue;
            }

            if ($column->isMappingRequired()) {
                $this->error("Falta una columna mapeada para '{$name}'. Asegúrate de que el encabezado del CSV coincida.");
                // limpiar el import y el archivo almacenado
                $import->delete();
                Storage::delete($storagePath);
                return 1;
            }
        }

        // Crear los jobs por chunks
        $jobs = [];

        // Obtener chunkSize del importer si es posible
        $chunkSize = 100;
        try {
            $reflection = new \ReflectionClass(UserImporter::class);
            if ($reflection->hasProperty('chunkSize')) {
                $prop = $reflection->getProperty('chunkSize');
                $prop->setAccessible(true);
                $value = $prop->getValue();
                if (is_int($value) && $value > 0) {
                    $chunkSize = $value;
                }
            }
        } catch (\ReflectionException $e) {
            // ignore and use default
        }

        $records = $csv->getRecords();
        $buffer = [];
        foreach ($records as $row) {
            $buffer[] = $row;

            if (count($buffer) >= $chunkSize) {
                $jobs[] = new ImportCsv($import, $buffer, $columnMap, $options);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            $jobs[] = new ImportCsv($import, $buffer, $columnMap, $options);
        }

        if (empty($jobs)) {
            $this->info('No se encontraron filas para procesar.');
            return 0;
        }

        Bus::batch($jobs)->dispatch();

        $this->info("✅ Importación iniciada");
        $this->info("Ejecuta 'php artisan queue:work' para procesar la importación");
        $this->info("O verifica el progreso en el panel de Filament");

        return 0;
    }
}
