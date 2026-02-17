<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dnc;
use App\Notifications\ExamDueNotification;
use Carbon\Carbon;

class TestSendExamDueNotifications extends Command
{
    /**
     * El nombre y la firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'app:test-send-exam-due-notifications
                            {--dnc=* : IDs de DNC a probar (opcional)}
                            {--threshold=* : Umbrales específicos (opcional; días_before)}';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Test: envía notificaciones a todos los usuarios asignados (incluyendo sin intentos) sin filtrar por fechas ni rangos';

    /**
     * Ejecutar el comando de consola.
     */
    public function handle(): void
    {
        $this->info('🔧 Iniciando prueba de envío de notificaciones de exámenes...');

        $dncIds          = $this->option('dnc');
        $thresholdFilter = $this->option('threshold');

        Dnc::with(['thresholds', 'assignedUsers', 'exams'])
            ->when(! empty($dncIds), fn($q) => $q->whereIn('id', $dncIds))
            ->chunk(10, function ($dncs) use ($thresholdFilter) {
                foreach ($dncs as $dnc) {
                    $this->info("Procesando DNC [{$dnc->id}] {$dnc->name}");

                    // Determinar los thresholds a probar
                    $thresholds = $dnc->thresholds;
                    if (! empty($thresholdFilter)) {
                        $thresholds = $thresholds->filter(fn($t) => in_array($t->days_before, $thresholdFilter));
                        $this->info(' — Umbrales filtrados: ' . implode(', ', $thresholdFilter));
                    }

                    // Lista BCC vacía para test
                    $bccList = [];

                    foreach ($dnc->assignedUsers as $user) {
                        $this->info(" • Usuario: {$user->email}");

                        // Intentos existentes
                        $pending = $user->examAttempts->whereNull('finished_at');

                        // Si no hay intentos, simular uno por examen
                        if ($pending->isEmpty()) {
                            $this->info("   - Sin intentos, simular exámenes de la DNC");
                            $pending = $dnc->exams->map(fn($exam) => (object) [
                                'exam' => $exam,
                                'id'   => 0,
                            ]);
                        }

                        foreach ($thresholds as $threshold) {
                            // Fecha de prueba
                            $targetDate = Carbon::now()->startOfDay()
                                ->addDays($threshold->days_before)
                                ->toDateString();

                            $this->info("   • Umbral {$threshold->days_before} días (fecha de prueba: {$targetDate})");

                            // Enviar test de notificación con nombre de la DNC
                            $this->info("     ↳ Enviando test a {$user->email}");
                            $user->notify(new ExamDueNotification($pending, $bccList, $dnc->name));
                        }
                    }
                }
            });

        $this->info('✅ Prueba de notificaciones completada.');
    }
}
