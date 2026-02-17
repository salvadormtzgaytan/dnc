<?php

namespace App\Http\Controllers;

use App\Exports\WrongQuestionsExport;
use App\Models\ExamAttempt;
use App\Models\ExamFeedback;
use App\Models\Question;
use App\Models\QuestionChoice;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use PDF;

class ExamResultController extends Controller
{
    /**
     * Descarga en Excel las preguntas que el usuario erró en un intento concreto.
     */
    public function downloadWrongByAttempt(Request $request, ExamAttempt $attempt)
    {
        // 1. Verificar que el intento pertenezca al usuario logueado
        if ($attempt->user_id !== $request->user()->id) {
            abort(403);
        }

        // 2. Sólo permitir intentos finalizados
        if ($attempt->status !== 'completed') {
            return redirect()->back()->with('error', 'Este intento aún no está completado.');
        }

        // 3. Recolectar respuestas del intento (array question_{id} => choiceId(s))
        $answers = $attempt->answers ?? [];

        $rows = [];

        foreach ($answers as $key => $value) {
            // Obtener ID de pregunta
            if (! preg_match('/^question_(\d+)$/', $key, $m)) {
                continue;
            }
            $questionId = (int) $m[1];

            // IDs elegidos por el usuario (puede ser array o single)
            $userChoiceIds = (array) $value;

            // Armar arrays sorted para comparar
            sort($userChoiceIds);

            // Obtener IDs de las opciones correctas
            $correctChoiceIds = QuestionChoice::query()
                ->where('question_id', $questionId)
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            // Si coincide, fue correcta; ignorar
            if ($correctChoiceIds === $userChoiceIds) {
                continue;
            }

            // Cargar la pregunta y textos
            $question = Question::find($questionId);
            if (! $question) {
                continue;
            }

            // Texto de la respuesta del usuario (si varias, juntamos con "; ")
            $userTexts = QuestionChoice::query()
                ->whereIn('id', $userChoiceIds)
                ->pluck('text')
                ->toArray();

            // Texto(s) de la(s) respuesta(s) correcta(s)
            $correctTexts = QuestionChoice::query()
                ->whereIn('id', $correctChoiceIds)
                ->pluck('text')
                ->toArray();

           /*  $rows[] = [
                'Pregunta'           => $question->text,
                'Tu respuesta'       => implode('; ', $userTexts) ?: '—',
                'Respuesta correcta' => implode('; ', $correctTexts) ?: '—',
            ]; */
             $rows[] = [
                'Pregunta'           => $question->text,
                'Tu respuesta'       => implode('; ', $userTexts) ?: '—',
            ];
        }

        // 4. Descarga vacía si no hubo fallos
        if (empty($rows)) {
            return redirect()->back()->with('error', '¡Felicidades! No hubo preguntas incorrectas en este intento.');
        }

        // 5. Preparar nombre de archivo
        $fileName = sprintf(
            'areas_mejora_intento_%d_%s.xlsx',
            $attempt->id,
            now()->format('Ymd_His')
        );

        // 6. Devolver export Excel
        return Excel::download(
            new WrongQuestionsExport(collect($rows)),
            $fileName
        );
    }


    public function downloadPdfByAttempt(Request $request, ExamAttempt $attempt)
    {
        // 1. Permiso y estado
        if ($attempt->user_id !== $request->user()->id || $attempt->status !== 'completed') {
            abort(403);
        }

        // 2. Variables para la vista
        $exam              = $attempt->exam;
        $percentage        = round($attempt->score, 1);
        $earnedScore       = $attempt->score;
        $maxScore          = $attempt->max_score;
        $started           = $attempt->started_at;
        $finished          = $attempt->finished_at;
        $durationFormatted = $attempt->durationFormatted();
        $correctCount      = $attempt->getCorrectCount();
        $chartImage = $request->input('chart_image');

        // Cálculo de número de intento
        $allAttempts = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderBy('finished_at')
            ->get();

        $attemptNumber = $allAttempts->search(fn($a) => $a->id === $attempt->id) + 1;

        // Obtener retroalimentación según porcentaje (ExamFeedback)
        $feedbackText = ExamFeedback::query()
            ->where('exam_id', $exam->id)
            ->where('min_score', '<=', $percentage)
            ->where('max_score', '>=', $percentage)
            ->value('feedback') 
            ?? ''; // cadena vacía si no hay feedback

        // Ranking (por concesionaria o global)
        $user    = $request->user();
        $profile = $user->profile;
        $dealership = optional($profile)->catalogStore
            ? optional($profile->catalogStore)->catalogDealership
            : null;

        $completed = ExamAttempt::where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->get();

        if ($dealership) {
            // IDs de usuarios en la concesionaria
            $userIds = $profile->catalogDealership
                ->stores
                ->flatMap(fn($store) => $store->userProfiles->pluck('user_id'))
                ->unique()
                ->toArray();

            $completed = $completed->whereIn('user_id', $userIds);
        }

        $bestPerUser = $completed
            ->groupBy('user_id')
            ->map(fn($at) => $at->max('score'));

        $orderedUsers   = $bestPerUser->sortDesc()->keys()->values();
        $rankPosition   = $orderedUsers->search($user->id) + 1;
        $rankTotalUsers = $orderedUsers->count();

        // 3. Generar y devolver PDF
        $pdf = Pdf::loadView('exams.result-pdf', [
            'exam'              => $exam,
            'attempt'           => $attempt,
            'percentage'        => $percentage,
            'earnedScore'       => $earnedScore,
            'maxScore'          => $maxScore,
            'started'           => $started,
            'finished'          => $finished,
            'durationFormatted' => $durationFormatted,
            'attemptNumber'     => $attemptNumber,
            'correctCount'      => $correctCount,
            'rankPosition'      => $rankPosition,
            'rankTotalUsers'    => $rankTotalUsers,
            'dealership'        => $dealership,
            'feedbackText'      => $feedbackText,
            'chartImage'        => $chartImage,
        ]);

        $fileName = "resultado_{$attempt->id}_" . now()->format('Ymd_His') . ".pdf";
        //return $pdf->stream('resultado-examen.pdf');
        return $pdf->download($fileName);
    }
}
