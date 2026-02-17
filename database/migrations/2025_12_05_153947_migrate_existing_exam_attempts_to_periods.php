<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Dnc;
use App\Models\DncPeriod;
use App\Models\ExamAttempt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dncs = Dnc::all();
        
        foreach ($dncs as $dnc) {
            $examIds = $dnc->exams()->pluck('exams.id');
            $attempts = ExamAttempt::whereIn('exam_id', $examIds)
                ->whereNull('dnc_period_id')
                ->orderBy('started_at')
                ->get();

            if ($attempts->isEmpty()) {
                // Si no hay intentos, crear período actual
                DncPeriod::create([
                    'dnc_id' => $dnc->id,
                    'start_date' => $dnc->start_date,
                    'end_date' => $dnc->end_date,
                    'is_current' => true,
                    'period_name' => $this->generatePeriodName($dnc->start_date, $dnc->end_date)
                ]);
                continue;
            }

            // Crear períodos históricos basados en fechas reales
            $periods = [];
            
            // Período 1: Julio - Agosto 2025
            $julyAugPeriod = DncPeriod::create([
                'dnc_id' => $dnc->id,
                'start_date' => '2025-07-01 00:00:00',
                'end_date' => '2025-08-31 23:59:59',
                'is_current' => false,
                'period_name' => 'Julio - Agosto 2025'
            ]);
            $periods[] = $julyAugPeriod;

            // Período 2: Octubre - 4 Diciembre 2025
            $octDecPeriod = DncPeriod::create([
                'dnc_id' => $dnc->id,
                'start_date' => '2025-10-01 00:00:00',
                'end_date' => '2025-12-04 23:59:59',
                'is_current' => false,
                'period_name' => 'Octubre - 4 Diciembre 2025'
            ]);
            $periods[] = $octDecPeriod;

            // Período actual
            $currentPeriod = DncPeriod::create([
                'dnc_id' => $dnc->id,
                'start_date' => $dnc->start_date,
                'end_date' => $dnc->end_date,
                'is_current' => true,
                'period_name' => $this->generatePeriodName($dnc->start_date, $dnc->end_date)
            ]);
            $periods[] = $currentPeriod;

            // Asignar intentos a períodos según su fecha
            foreach ($attempts as $attempt) {
                $attemptDate = $attempt->started_at;
                $assignedPeriod = null;

                if ($attemptDate >= '2025-07-01' && $attemptDate <= '2025-08-31') {
                    $assignedPeriod = $julyAugPeriod;
                } elseif ($attemptDate >= '2025-10-01' && $attemptDate <= '2025-12-04') {
                    $assignedPeriod = $octDecPeriod;
                } else {
                    $assignedPeriod = $currentPeriod;
                }

                $attempt->update(['dnc_period_id' => $assignedPeriod->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpiar dnc_period_id de todos los intentos
        ExamAttempt::query()->update(['dnc_period_id' => null]);
        
        // Eliminar todos los períodos
        DncPeriod::query()->delete();
    }

    private function generatePeriodName($startDate, $endDate): string
    {
        if (!$startDate && !$endDate) {
            return 'Período inicial';
        }

        if (!$startDate) {
            return 'Hasta ' . $endDate->format('d/m/Y');
        }

        if (!$endDate) {
            return 'Desde ' . $startDate->format('d/m/Y');
        }

        $startMonth = $startDate->format('M Y');
        $endMonth = $endDate->format('M Y');

        if ($startMonth === $endMonth) {
            return $startMonth . ' (inicial)';
        }

        return $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y') . ' (inicial)';
    }
};