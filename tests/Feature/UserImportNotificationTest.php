<?php

use App\Mail\UserImportNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Imports\Models\Import;

test('envía resumen de importación con adjunto', function () {
    Mail::fake();
    Storage::fake('local');

    $admin = User::factory()->create();

    $importedUsers = User::factory()->count(3)->create();
    $userIds = $importedUsers->pluck('id');
    $passwords = $importedUsers->mapWithKeys(fn ($u) => [$u->email => 'ClaveTest123']);

    $import = new Import();
    $csvPath = 'temp/test_' . now()->timestamp . '.csv';

    UserImportNotification::generateCsvFile($csvPath, $userIds, $passwords, $import);
    $sampleUsers = UserImportNotification::getSampleUsers($userIds);

    Mail::to($admin)->send(new UserImportNotification(
        importedCount: $userIds->count(),
        failedCount: 0,
        csvPath: $csvPath,
        sampleUsers: $sampleUsers
    ));

    Mail::assertSent(UserImportNotification::class, function ($mail) use ($admin, $csvPath) {
        return $mail->hasTo($admin->email)
            && $mail->envelope()->subject === 'Resumen de importación: 3 usuarios'
            && collect($mail->attachments())->contains(fn ($att) =>
                str_contains($att->path, 'temp/') && $att->as === 'usuarios_importados_' . now()->format('Y-m-d') . '.csv'
            );
    });
});
