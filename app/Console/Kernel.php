<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log; // Importa la fachada Log

class Kernel extends ConsoleKernel
{
    /**
     * Define el schedule de comandos de la aplicación.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule
            ->command('app:send-exam-due-notifications')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onFailure(function (): void {
                Log::critical('El comando app:send-exam-due-notifications falló');
            })
            ->runInBackground();
    }

    /**
     * Registra los comandos de consola de la aplicación.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
