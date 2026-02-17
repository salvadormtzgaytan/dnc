<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\DncUserAssignment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Traemos únicamente las DNC asignadas
        $assignments = DncUserAssignment::with([
            'dnc.exams' => function ($q) use ($user) {
                $q->with(['attempts' => function ($q2) use ($user) {
                    $q2->where('user_id', $user->id);
                }]);
            },
            'dnc.userOverrides' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            },
        ])
        ->where('user_id', $user->id)
        ->get();

        // Recorremos cada asignación para calcular métricas
        $dncs = $assignments
            ->map(fn($assignment) => $assignment->dnc)
            ->filter()
            ->map(function ($dnc) use ($user) {
                // Calcular si está expirado para este usuario
                $dnc->is_expired = $dnc->isExpiredForUser($user->id);
                
                // Si no tiene exámenes, dejamos métricas en null
                if ($dnc->exams->isEmpty()) {
                    $dnc->average_score = null;
                    $dnc->best_score    = null;
                    $dnc->best_exam     = null;
                    return $dnc;
                }

                $totalScore = 0;
                $countExams = 0;
                $bestScore  = 0;
                $bestExam   = null;

                foreach ($dnc->exams as $exam) {
                    $countExams++;

                    // Intentos completados
                    $completed = $exam->attempts->where('status', 'completed');
                    $maxScore  = $completed->max('score') ?: 0;

                    $totalScore += $maxScore;

                    if ($maxScore > $bestScore) {
                        $bestScore = $maxScore;
                        $bestExam  = $exam->name;
                    }
                }

                $dnc->average_score = $countExams
                    ? round($totalScore / $countExams, 2)
                    : null;
                $dnc->best_score    = $bestScore;
                $dnc->best_exam     = $bestExam;

                return $dnc;
            })
            ->values();

        return view('dashboard', compact('dncs'));
    }
}
