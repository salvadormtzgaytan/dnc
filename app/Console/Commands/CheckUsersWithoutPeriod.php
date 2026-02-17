<?php

namespace App\Console\Commands;

use App\Models\Dnc;
use App\Models\ExamAttempt;
use Illuminate\Console\Command;

class CheckUsersWithoutPeriod extends Command
{
    protected $signature = 'dnc:check-users-without-period';
    protected $description = 'Busca intentos de examen sin período asignado';

    public function handle()
    {
        $this->info('=== INTENTOS SIN PERÍODO ASIGNADO ===');
        $this->newLine();

        $dncs = Dnc::with(['exams', 'periods'])->get();
        $totalIssues = 0;

        foreach ($dncs as $dnc) {
            $examIds = $dnc->exams->pluck('id');
            
            foreach ($dnc->periods as $period) {
                // Buscar intentos dentro del rango del período pero sin período asignado
                $attemptsWithoutPeriod = ExamAttempt::whereIn('exam_id', $examIds)
                    ->whereBetween('started_at', [$period->start_date, $period->end_date])
                    ->whereNull('dnc_period_id')
                    ->count();

                if ($attemptsWithoutPeriod > 0) {
                    $totalIssues += $attemptsWithoutPeriod;
                    
                    $this->warn("DNC: {$dnc->name} (ID: {$dnc->id})");
                    $this->line("  Período: {$period->period_name}");
                    $this->line("  Rango: {$period->start_date->format('d/m/Y')} - {$period->end_date->format('d/m/Y')}");
                    $this->line("  ⚠️  Intentos sin período: {$attemptsWithoutPeriod}");
                    $this->newLine();
                }
            }
        }

        if ($totalIssues === 0) {
            $this->info('✓ Todos los intentos tienen período asignado');
        } else {
            $this->error("Total intentos sin período: {$totalIssues}");
        }

        return 0;
    }
}
