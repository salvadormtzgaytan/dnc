<?php

use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\Imports\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

test('el comando dispatcha jobs ImportCsv para un CSV válido', function () {
    Bus::fake();

    $admin = User::factory()->create();

    $csv = "name,email\nJuan,j@example.com\nAna,a@example.com\n";
    $file = storage_path('app/temp_users.csv');
    file_put_contents($file, $csv);

    $exitCode = Artisan::call('users:import', ['file' => $file]);

    expect($exitCode)->toBe(0);

    $import = Import::where('file_name', basename($file))->first();
    expect($import)->not->toBeNull();
    expect($import->user_id)->toBe($admin->id);

    Bus::assertBatched(function ($batch) {
        return collect($batch->jobs)->contains(fn ($job) => $job instanceof ImportCsv);
    });

    @unlink($file);
    // cleanup stored import file
    @unlink(storage_path('app/imports/' . basename($file)));
});
