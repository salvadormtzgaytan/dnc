<?php

namespace App\Console\Commands;

use App\Models\DncPeriod;
use App\Models\ExamAttempt;
use Illuminate\Console\Command;

class FixExamAttemptsPeriods extends Command
{
    protected $signature = 'exam:fix-periods {period_id}';
    protected $description = 'Corrige los intentos de examen asignándolos al período correcto según su fecha y DNC';

    public function handle()
    {
        $periodId = $this->argument('period_id');
        
        $period = DncPeriod::with('dnc')->find($periodId);
        
        if (!$period) {
            $this->error("Período con ID {$periodId} no encontrado.");
            return 1;
        }

        $this->info("DNC: {$period->dnc->name}");
        $this->info("Período: {$period->period_name}");
        $this->info("Desde: {$period->start_date}");
        $this->info("Hasta: {$period->end_date}");
        $this->newLine();

        // Obtener IDs de exámenes de esta DNC
        $examIds = $period->dnc->exams()->pluck('exams.id');

        if ($examIds->isEmpty()) {
            $this->warn('Esta DNC no tiene exámenes asignados.');
            return 0;
        }

        $this->info("Exámenes en esta DNC: {$examIds->count()}");

        // Buscar intentos de esos exámenes dentro del rango de fechas
        $attempts = ExamAttempt::whereIn('exam_id', $examIds)
            ->whereBetween('started_at', [$period->start_date, $period->end_date])
            ->where(function($query) use ($periodId) {
                $query->whereNull('dnc_period_id')
                      ->orWhere('dnc_period_id', '!=', $periodId);
            })
            ->get();

        if ($attempts->isEmpty()) {
            $this->info('No se encontraron intentos para corregir.');
            return 0;
        }

        $this->info("Se encontraron {$attempts->count()} intentos para corregir.");
        
        if (!$this->confirm('¿Deseas continuar?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $bar = $this->output->createProgressBar($attempts->count());
        $bar->start();

        $updated = 0;
        foreach ($attempts as $attempt) {
            $attempt->update(['dnc_period_id' => $periodId]);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Se actualizaron {$updated} intentos correctamente.");

        return 0;
    }
}
