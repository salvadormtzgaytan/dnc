<?php

namespace App\Http\Controllers;

use App\Models\Dnc;
use App\Models\DncUserAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DncExamController extends Controller
{
    /**
     * Dashboard principal:
     * Lista todas las DNC asignadas al usuario.
     * Ruta: GET /dncs  name=dncs.list
     */
    public function list()
    {
        $user = Auth::user();

        // Obtener solo las DNC asignadas
        $dncs = DncUserAssignment::with('dnc')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('dnc')
            ->filter()
            ->values();

        // Renderiza la vista con todas las DNCs
        return view('dnc.list', compact('dncs'));
    }

    /**
     * Detalle de una DNC:
     * Muestra la DNC seleccionada con sus exámenes y métricas.
     * Ruta: GET /dnc/{dnc}/exams  name=dncs.exams.list
     */
    public function listExam(Dnc $dnc)
    {
        $user = Auth::user();

        // 1) Verificar que la DNC esté asignada al usuario
        $assigned = DncUserAssignment::where('user_id', $user->id)
            ->where('dnc_id', $dnc->id)
            ->exists();

        abort_unless($assigned, 403);

        // 2) Cargar exámenes, intentos y anulaciones del usuario
        $dnc->load([
            'exams' => function ($q) use ($user) {
                $q->with(['attempts' => fn($q2) => $q2->where('user_id', $user->id)]);
            },
            'userOverrides' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }
        ]);

        // 3) Calcular estado expirado para este usuario específico
        $dnc->is_expired = $dnc->isExpiredForUser($user->id);

        // 4) Inicializar métricas
        $totalScoreSum = 0;
        $examCount     = 0;
        $bestScore     = 0;
        $bestExam      = null;
        $chartLabels   = [];
        $chartScores   = [];

        foreach ($dnc->exams as $exam) {
            $examCount++;

            // Intentos completados e intentos en curso
            $completed = $exam->attempts->where('status', 'completed');
            $maxScore  = $completed->max('score') ?? 0;

            $inProgress = $exam->attempts
                ->where('status', 'in_progress')
                ->sortByDesc('resumed_at')
                ->first();

            // 5) Calcular progreso
            if ($inProgress
                && is_array($inProgress->question_order)
                && is_array($inProgress->answers)
            ) {
                $totalQuestions = count($inProgress->question_order);
                $answered = count(array_filter(
                    $inProgress->answers,
                    fn($v, $k) => str_starts_with($k, 'question_') && $v !== null,
                    ARRAY_FILTER_USE_BOTH
                ));
                $exam->progress = $totalQuestions > 0
                    ? round(($answered / $totalQuestions) * 100)
                    : 0;
            } elseif ($completed->isNotEmpty()) {
                $exam->progress = 100;
            } else {
                $exam->progress = null;
            }

            // 6) Acumular para promedio y gráfica
            $totalScoreSum  += $maxScore;
            $chartLabels[]  = $exam->name;
            $chartScores[]  = $maxScore;

            // 7) Mejor puntuación
            if ($maxScore > $bestScore) {
                $bestScore = $maxScore;
                $bestExam  = $exam->name;
            }
        }

        // 8) Asignar métricas a la DNC
        $dnc->average_score = $examCount > 0
            ? round($totalScoreSum / $examCount, 2)
            : null;
        $dnc->best_score   = $bestScore;
        $dnc->best_exam    = $bestExam;
        $dnc->chart_labels = $chartLabels;
        $dnc->chart_scores = $chartScores;

        // 9) Renderizar la vista de detalle
        return view('dnc.detail', compact('dnc'));
    }
}
