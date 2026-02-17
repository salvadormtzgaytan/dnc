<?php

namespace App\Console\Commands;

use App\Models\DncPeriod;
use App\Models\ExamAttempt;
use Illuminate\Console\Command;

class ConsolidateDncPeriods extends Command
{
    protected $signature = 'dnc:consolidate-periods';
    protected $description = 'Consolida períodos duplicados manteniendo el de mayor rango';

    public function handle()
    {
        $this->info('=== CONSOLIDACIÓN DE PERÍODOS ===');
        $this->newLine();

        // Agrupar períodos por DNC
        $dncs = \App\Models\Dnc::with('periods')->get();

        foreach ($dncs as $dnc) {
            $this->info("DNC: {$dnc->name} (ID: {$dnc->id})");
            
            // Agrupar períodos por rango de fechas similar
            $periodGroups = $dnc->periods->groupBy(function($period) {
                return $period->start_date->format('Y-m-d');
            });

            foreach ($periodGroups as $startDate => $periods) {
                if ($periods->count() <= 1) {
                    continue;
                }

                $this->warn("  Encontrados {$periods->count()} períodos con inicio similar");

                // Encontrar el período con el rango más grande
                $masterPeriod = $periods->sortByDesc(function($period) {
                    return $period->end_date;
                })->first();

                $this->info("  Período maestro: ID {$masterPeriod->id} ({$masterPeriod->start_date} - {$masterPeriod->end_date})");

                // Obtener IDs de exámenes de esta DNC
                $examIds = $dnc->exams()->pluck('exams.id');

                foreach ($periods as $period) {
                    if ($period->id === $masterPeriod->id) {
                        continue;
                    }

                    $this->line("  Procesando período ID {$period->id}...");

                    // Mover intentos al período maestro
                    $moved = ExamAttempt::where('dnc_period_id', $period->id)
                        ->whereIn('exam_id', $examIds)
                        ->update(['dnc_period_id' => $masterPeriod->id]);

                    $this->line("    Movidos: {$moved} intentos");

                    // Eliminar período duplicado
                    $period->delete();
                    $this->line("    Período eliminado");
                }

                // Asignar intentos sin período dentro del rango
                $unassigned = ExamAttempt::whereIn('exam_id', $examIds)
                    ->whereBetween('started_at', [$masterPeriod->start_date, $masterPeriod->end_date])
                    ->where(function($query) use ($masterPeriod) {
                        $query->whereNull('dnc_period_id')
                              ->orWhere('dnc_period_id', '!=', $masterPeriod->id);
                    })
                    ->update(['dnc_period_id' => $masterPeriod->id]);

                $this->info("  ✓ Asignados {$unassigned} intentos sin período");
            }

            $this->newLine();
        }

        $this->info('✓ Consolidación completada');
        return 0;
    }
}
