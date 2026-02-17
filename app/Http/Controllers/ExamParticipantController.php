<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\View\View;
use App\Models\ExamAttempt;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use App\Models\QuestionChoice;
use App\Models\ExamUserOverride;
use App\Models\ExamAttemptAnswer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ExamParticipantController extends Controller
{

    /**
     * Muestra la página actual del examen y controla la navegación secuencial/libre.
     * Si no viene `?page=`, posiciona en la página de la primera pregunta sin responder.
     *
     * @param  Exam  $exam
     * @return View|RedirectResponse
     */
    public function start(Exam $exam): View|RedirectResponse
    {
        $user = Auth::user();
        $override = $this->getOverride($exam, $user);
        $errors = $this->checkAccess($exam, $user, $override);

        if (! empty($errors)) {
            return view('exams.restricted', compact('exam', 'errors'));
        }

        // 1) Obtener (o crear) el intento
        $attempt = $this->getOrCreateAttempt($exam, $user);

        // 2) Registrar tiempo activo
        if ($attempt->resumed_at) {
            $attempt->accumulateActiveTime();
        } else {
            $attempt->resumed_at = now();
            $attempt->save();
        }

        // 3) Calcular tiempo restante
        $timeLimit = $override->time_limit ?? $exam->time_limit;
        $timeRemaining = $timeLimit !== null
            ? $attempt->getRemainingTime($timeLimit)
            : null;

        if ($timeLimit !== null && $timeRemaining <= 0) {
            $attempt->complete();
            return view('exams.start', [
                'exam' => $exam,
                'attempt' => $attempt,
                'questions' => collect(),
                'maxPageReached' => 1,
                'timeLimit' => 0,
                'showTimeoutModal' => true,
            ]);
        }

        // 4) Recuperar preguntas en el orden establecido
        $orderedIds = $attempt->question_order ?: $exam->questions()->pluck('questions.id')->toArray();
        $allQuestions = Question::with('choices')
            ->whereIn('id', $orderedIds)
            ->get()
            ->sortBy(fn($q) => array_search($q->id, $orderedIds))
            ->values();

        // 5) Inyectar siempre el mismo orden de choices guardado
        foreach ($allQuestions as $question) {
            $key = 'question_' . $question->id;
            if (isset($attempt->choice_order[$key])) {
                $ids = $attempt->choice_order[$key];
                $ordered = collect($ids)
                    ->map(fn($id) => $question->choices->firstWhere('id', $id))
                    ->filter();
                $question->setRelation('choices', $ordered);
            }
        }

        $perPage = $exam->questions_per_page ?? 1;

        // 6) Determinar página actual (URL o primera sin responder)
        if (request()->has('page')) {
            $currentPage = (int) request('page');
        } else {
            /* $currentPage = $this->findFirstUnansweredPage(
                $orderedIds,
                $attempt->answers ?? [],
                $perPage
            ); */
            $currentPage = $attempt->last_page_reached ?? 1;
        }

        // 7) Paginar
        $questions = $this->paginateQuestions($allQuestions, $perPage, $currentPage);

        // 8) Calcular hasta dónde ha llegado el usuario
        $rawMaxPageReached = $this->calculateMaxPageReached($attempt, $orderedIds, $perPage);

        // 9) Determinar límite de páginas según modo
        if ($exam->navigation_method === 'sequential') {
            $allowedPage = min($rawMaxPageReached + 1, $questions->lastPage());
        } else {
            $allowedPage = $questions->lastPage();
        }

        // 10) Bloquear si pide página superior al permitido
        if ($currentPage > $allowedPage) {
            return redirect()
                ->route('exam.start', [$exam->id, 'page' => $allowedPage])
                ->with('error', 'No puedes acceder a esa página todavía.');
        }

        // 11) Renderizar
        return view('exams.start', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'maxPageReached' => $rawMaxPageReached,
            'allowedPage' => $allowedPage,
            'timeLimit' => $timeRemaining,
        ]);
    }

    /**
     * Busca la primera página que tenga al menos una pregunta sin responder.
     *
     * @param  array  $orderedIds    IDs de preguntas en orden
     * @param  array  $answers       Respuestas guardadas ['question_{id}' => ...]
     * @param  int    $perPage       Preguntas por página
     * @return int                   Número de página (1‑based)
     */
    private function findFirstUnansweredPage(array $orderedIds, array $answers, int $perPage): int
    {
        $unanswered = collect($orderedIds)
            ->filter(fn($id) => ! isset($answers['question_' . $id]))
            ->values();

        if ($unanswered->isEmpty()) {
            return 1;
        }

        $firstIdx = collect($orderedIds)->search($unanswered->first());

        return intdiv($firstIdx, $perPage) + 1;
    }


    /**
     * Obtiene el override de fechas y tiempo para el usuario.
     */
    private function getOverride(Exam $exam, $user): ?ExamUserOverride
    {
        return ExamUserOverride::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Verifica si el usuario puede acceder al examen.
     */
    private function checkAccess(Exam $exam, $user, ?ExamUserOverride $override): array
    {
        $now = now();
        $errors = [];

        if (! $exam->enabled) {
            $errors[] = "Este examen no está habilitado actualmente.";
        }

        // Validar si el examen tiene preguntas asociadas
        if ($exam->questions()->count() === 0) {
            $errors[] = "Este examen aún no tiene preguntas asociadas.";
        }

        if (! $exam->dncs()
            ->whereHas('assignedUsers', fn($q) => $q->where('users.id', $user->id))
            ->exists()) {
            $errors[] = "No estás inscrito en este examen.";
        }

        $startAt = $override->start_at ?? $exam->start_at;
        $endAt = $override->end_at ?? $exam->end_at;
        $maxAttempts = $override->max_attempts ?? $exam->max_attempts;

        if ($startAt && $now->lt($startAt)) {
            $errors[] = "Este examen estará disponible desde el " . $startAt->format('d/m/Y H:i');
        }

        if ($endAt && $now->gt($endAt)) {
            $errors[] = "Este examen cerró el " . $endAt->format('d/m/Y H:i');
        }

        if ($maxAttempts !== null) {
            $finished = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->count();
            $active = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->exists();

            if ($finished >= $maxAttempts && ! $active) {
                $errors[] = "Has alcanzado el número máximo de intentos permitidos.";
            }
        }

        return $errors;
    }

    /**
     * Obtiene o crea un intento de examen en progreso.
     */
    private function getOrCreateAttempt(Exam $exam, $user): ExamAttempt
    {
        // IDs de preguntas
        $questionIds = $exam->questions()->pluck('questions.id')->toArray();

        // Obtener el período actual de la DNC
        $dncPeriodId = null;
        $dnc = $exam->dncs()->first(); // Asumiendo que un examen pertenece a una DNC
        if ($dnc) {
            $currentPeriod = $dnc->getCurrentOrCreatePeriod();
            $dncPeriodId = $currentPeriod->id;
        }

        // Intento (nuevo o existente)
        $attempt = ExamAttempt::firstOrCreate(
            [
                'exam_id' => $exam->id,
                'user_id' => $user->id,
                'status' => 'in_progress',
            ],
            [
                'started_at' => now(),
                'question_order' => $exam->shuffle_questions
                    ? collect($questionIds)->shuffle()->toArray()
                    : $questionIds,
                'answers' => [],
                'choice_order' => [],
                'dnc_period_id' => $dncPeriodId,
            ]
        );

        // Si es recién creado, inicializamos choice_order
        if (empty($attempt->choice_order)) {
            $choiceOrder = [];
            // cargamos preguntas con sus opciones
            $questions = Question::with('choices')
                ->whereIn('id', $questionIds)
                ->get();

            foreach ($questions as $question) {
                $ids = $question->choices->pluck('id')->toArray();
                // si hay que mezclarlas, barajamos
                if ($question->shuffle_choices) {
                    shuffle($ids);
                }
                $choiceOrder['question_' . $question->id] = $ids;
            }

            $attempt->choice_order = $choiceOrder;
            $attempt->save();
        }

        return $attempt;
    }


    /**
     * Pagina una colección de preguntas.
     */
    private function paginateQuestions(Collection $allQuestions, int $perPage, int $currentPage): LengthAwarePaginator
    {
        $offset = ($currentPage - 1) * $perPage;

        return new LengthAwarePaginator(
            $allQuestions->slice($offset, $perPage),
            $allQuestions->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Calcula la última página en la que hay al menos una respuesta.
     */
    private function calculateMaxPageReached(ExamAttempt $attempt, array $orderedIds, int $perPage): int
    {
        $answeredIds = collect($attempt->answers ?? [])
            ->keys()
            ->map(fn($k) => (int) str_replace('question_', '', $k))
            ->filter();

        $lastAnsweredIndex = collect($orderedIds)
            ->filter(fn($id) => $answeredIds->contains($id))
            ->keys()
            ->last();

        return is_null($lastAnsweredIndex)
            ? 1
            : intval(floor($lastAnsweredIndex / $perPage)) + 1;
    }

    /**
     * Guarda las respuestas de la página actual y maneja la navegación.
     */
    /**
     * Guarda las respuestas de la página actual y maneja la navegación.
     */
    public function savePage(Request $request, Exam $exam, int $page): RedirectResponse
    {
        $user = $request->user();

        // 1) Recuperar intento en progreso
        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $orderedIds = $attempt->question_order ?? [];
        $questionsPerPage = $exam->questions_per_page ?? 1;

        // 2) Calcular IDs de pregunta de la página actual
        $offset = ($page - 1) * $questionsPerPage;
        $currentIds = array_slice($orderedIds, $offset, $questionsPerPage);

        // 3) Cargar respuestas actuales y sobreescribir con lo enviado
        $answers = $attempt->answers ?? [];
        foreach ($currentIds as $qid) {
            $key = "question_$qid";
            if ($request->has($key)) {
                $val = $request->input($key);
                $answers[$key] = is_array($val)
                    ? array_map('intval', $val)
                    : (int) $val;
            } else {
                unset($answers[$key]);
            }
        }
        $attempt->answers = $answers;
        $attempt->save();

        $action = $request->input('action');

        // 4) Si es modo sequential y vienen de “next” o “finish”, validar la página actual
        if ($exam->navigation_method === 'sequential' && in_array($action, ['next', 'finish'])) {
            $missing = [];
            foreach ($currentIds as $qid) {
                if (! isset($answers["question_$qid"])) {
                    $missing[] = $qid;
                }
            }
            if (! empty($missing)) {
                return redirect()
                    ->route('exam.start', [$exam->id, 'page' => $page])
                    ->with('error', 'Debes responder las preguntas: ' . implode(', ', $missing) . ' antes de continuar.');
            }
        }

        // 5) Si la acción es “finish”, validar TODO el examen
        if ($action === 'finish') {
            $missingAll = [];
            foreach ($orderedIds as $qid) {
                if (! isset($answers["question_$qid"])) {
                    $missingAll[] = $qid;
                }
            }
            if (! empty($missingAll)) {
                // Agrupar faltantes por página
                $byPage = [];
                foreach ($missingAll as $qid) {
                    $idx = array_search($qid, $orderedIds);
                    $pg = intdiv($idx, $questionsPerPage) + 1;
                    $num = $idx + 1; // número global de pregunta
                    $byPage[$pg][] = $num;
                }
                ksort($byPage);
                $parts = [];
                foreach ($byPage as $pg => $nums) {
                    $parts[] = "Página {$pg}: " . implode(',', $nums);
                }
                $detail = implode('; ', $parts);

                return redirect()
                    ->route('exam.start', [$exam->id, 'page' => $page])
                    ->with('error', "Debes responder las preguntas ({$detail}) antes de finalizar el examen.");
            }

            // Si llegamos aquí, todo contestado → completar intento
            $attempt->complete();
            return redirect()->route('exam.finish', $exam->id);
        }

        // 6) Navegación normal: prev / next
        if ($action === 'prev') {
            return redirect()->route('exam.start', [$exam->id, 'page' => max(1, $page - 1)]);
        }
        if ($action === 'next') {
            return redirect()->route('exam.start', [$exam->id, 'page' => $page + 1]);
        }

        // 7) Default: recargar misma página
        return redirect()->route('exam.start', [$exam->id, 'page' => $page]);
    }


    /**
     * Guarda una respuesta individual vía AJAX.
     */
    public function saveAnswer(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'question_id' => 'required|integer',
            'value' => 'required',
        ]);

        $user = $request->user();
        $override = $this->getOverride($exam, $user);
        $timeLimit = $override->time_limit ?? $exam->time_limit;

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->firstOrFail();

        if ($timeLimit !== null && $attempt->getRemainingTime($timeLimit) <= 0) {
            $attempt->complete();
            return response()->json([
                'success' => false,
                'message' => 'El tiempo del examen ha expirado. No puedes continuar.',
            ], 403);
        }

        $questionId = (int) $request->input('question_id');
        $key = 'question_' . $questionId;
        $value = $request->input('value');

        if (!in_array($questionId, $attempt->question_order ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Pregunta no válida para este examen.',
            ], 422);
        }

        if (!$exam->questions()->where('questions.id', $questionId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La pregunta no pertenece a este examen.',
            ], 422);
        }

        // Convertir valor a array para manejar tanto preguntas simples como múltiples
        $answerValues = is_array($value) ? $value : [$value];

        // Eliminar respuestas existentes para esta pregunta
        ExamAttemptAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->delete();

        // Guardar cada respuesta seleccionada
        foreach ($answerValues as $selectedChoiceId) {
            $correctChoiceId = QuestionChoice::where('question_id', $questionId)
                ->where('is_correct', true)
                ->value('id');

            ExamAttemptAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'selected_choice_id' => $selectedChoiceId,
                'correct_choice_id' => $correctChoiceId,
            ]);
        }

        // Actualizar el campo JSON answers
        $answers = $attempt->answers ?? [];
        $answers[$key] = is_array($value) ? array_map('intval', $value) : (int) $value;
        $attempt->answers = $answers;
        $attempt->save();

        return response()->json([
            'success' => true,
            'message' => 'Respuesta guardada correctamente.',
        ]);
    }

    /**
     * Finaliza el intento vía AJAX cuando se agota el tiempo.
     */
    public function finishAjax(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un intento activo.',
            ], 404);
        }

        $attempt->complete();
        return response()->json([
            'success' => true,
            'message' => 'Intento finalizado correctamente.',
        ]);
    }

    /**
     * Muestra el resumen final del examen con calificaciones y gráficos,
     * o redirige si hay un intento en progreso.
     *
     * @param  Exam  $exam
     * @return View|RedirectResponse
     */
    public function finish(Exam $exam, ?ExamAttempt $attempt = null): View|RedirectResponse
    {
        $user = auth()->user();

        if (ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->exists()
        ) {
            return redirect()
                ->route('exam.start', [$exam->id])
                ->with('error', 'Tienes un intento en progreso. Debes finalizarlo primero.');
        }

        // Validar intento específico o buscar el más reciente
        if ($attempt) {
            if ($attempt->user_id !== $user->id || $attempt->exam_id !== $exam->id || $attempt->status !== 'completed') {
                abort(403, 'No autorizado para ver este intento.');
            }
        } else {
            $attempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->latest('finished_at')
                ->first();
        }

        $maxScore = 0;
        $earnedScore = 0;
        $correctCount = 0;
        $correctPerSegment = [];

        foreach ($attempt->answers ?? [] as $key => $value) {
            $questionId = (int) str_replace('question_', '', $key);
            $question = $exam->questions()->with(['choices', 'segment'])->find($questionId);

            if (! $question) {
                continue;
            }

            // 1) Acumular el puntaje máximo
            $maxScore += $question->default_score;

            // 2) Comprobar si la respuesta fue correcta
            $correctIds = $question->choices
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values();
            $userIds = collect((array) $value)
                ->sort()
                ->values();

            if ($correctIds->all() === $userIds->all()) {
                // Sumar puntaje ganado
                $earnedScore += $question->default_score;

                // Incrementar contador total de correctas
                $correctCount++;

                // Incrementar contador por segmento
                if ($question->segment) {
                    $segName = $question->segment->name;
                    $correctPerSegment[$segName] = ($correctPerSegment[$segName] ?? 0) + 1;
                }
            }
        }


        $percentage = $maxScore > 0
            ? round(($earnedScore / $maxScore) * 100, 2)
            : 0;

        $attempt->score = $percentage;
        $attempt->max_score = $maxScore;
        $attempt->save();

        $started = $attempt->started_at;
        $finished = $attempt->finished_at ?? now();
        $durationFormatted = gmdate('H:i:s', $attempt->active_duration ?? 0);
        $attemptNumber = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('id')
            ->search($attempt->id) + 1;
        $categoryData = $this->getCategoryScores($exam, $attempt);
        $feedbackText = $exam->feedbacks()
            ->where('min_score', '<=', $percentage)
            ->where('max_score', '>=', $percentage)
            ->value('feedback')
            ?? 'No hay retroalimentación disponible para tu calificación.';
        $segmentData = $this->getSegmentScores($exam, $attempt);
        $allCompletedAttempts = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('finished_at')
            ->get();

        // 1) Identificar la concesionaria del usuario (si existe)
        $user       = Auth::user();
        $profile    = $user->profile; // asume relación profile() en User
        $dealership = optional($profile)->catalogStore
            ? optional($profile->catalogStore)->catalogDealership
            : null;

        // 2) Traer todos los intentos completados de este examen
        $completed = ExamAttempt::where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->get();

        // 3) Filtrar por concesionaria si aplica
        if ($dealership) {
            // Obtener IDs de usuarios de esa concesionaria
            $userIds = UserProfile::where('catalog_dealership_id', $dealership->id)
                ->pluck('user_id')
                ->toArray();

            // Quedarse sólo con intentos de esos usuarios
            $completed = $completed->whereIn('user_id', $userIds);
        }

        // 4) Agrupar por usuario y calcular su mejor score
        $bestPerUser = $completed
            ->groupBy('user_id')
            ->map(fn($attemps, $userId) => $attemps->max('score'));

        // 5) Ordenar de mayor a menor y extraer user_ids
        $orderedUsers = $bestPerUser
            ->sortDesc()
            ->keys()
            ->values();

        // 6) Ranking global
        $rankPosition    = $orderedUsers->search($user->id) + 1;
        $rankTotalUsers  = $orderedUsers->count();

        return view('exams.result', compact(
            'exam',
            'attempt',
            'percentage',
            'earnedScore',
            'maxScore',
            'started',
            'finished',
            'durationFormatted',
            'attemptNumber',
            'categoryData',
            'segmentData',
            'correctCount',
            'correctPerSegment',
            'feedbackText',
            'allCompletedAttempts',
            'rankPosition',
            'rankTotalUsers',
            'dealership'
        ));
    }

    /**
     * Calcula porcentaje por categoría para el gráfico de barras.
     */
    private function getCategoryScores(Exam $exam, ExamAttempt $attempt): array
    {
        $categories = [];

        foreach ($attempt->answers ?? [] as $key => $value) {
            $questionId = (int) str_replace('question_', '', $key);
            $question = $exam->questions()
                ->with(['choices', 'category'])
                ->find($questionId);

            if (! $question || ! $question->category) {
                continue;
            }

            $name = $question->category->name;
            $score = $question->default_score ?? 1;

            if (! isset($categories[$name])) {
                $categories[$name] = ['earned' => 0, 'max' => 0];
            }

            $categories[$name]['max'] += $score;

            $correctIds = $question->choices
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values();
            $userIds = collect((array) $value)
                ->sort()
                ->values();

            if ($correctIds->all() === $userIds->all()) {
                $categories[$name]['earned'] += $score;
            }
        }

        $labels = [];
        $scores = [];
        foreach ($categories as $label => $data) {
            $labels[] = $label;
            $scores[] = $data['max'] > 0
                ? round(($data['earned'] / $data['max']) * 100, 2)
                : 0;
        }

        return ['labels' => $labels, 'scores' => $scores];
    }

    /**
     * Calcula el porcentaje de aciertos por segmento para el gráfico de barras.
     *
     * @param  Exam         $exam
     * @param  ExamAttempt  $attempt
     * @return array        ['labels' => […nombres de segmentos…], 'scores' => […porcentajes…]]
     */
    private function getSegmentScores(Exam $exam, ExamAttempt $attempt): array
    {
        $segments = [];

        // Recorremos cada respuesta guardada
        foreach ($attempt->answers ?? [] as $key => $value) {
            $questionId = (int) str_replace('question_', '', $key);
            $question = $exam->questions()
                ->with(['choices', 'segment'])
                ->find($questionId);

            // Si no existe pregunta o no está asignada a segmento, la ignoramos
            if (! $question || ! $question->segment) {
                continue;
            }

            $segName = $question->segment->name;
            $questionScore = $question->default_score ?? 1;

            // Inicializar acumuladores
            if (! isset($segments[$segName])) {
                $segments[$segName] = [
                    'earned' => 0,
                    'max' => 0,
                ];
            }

            // Sumar el máximo posible
            $segments[$segName]['max'] += $questionScore;

            // Comprobar si la respuesta es correcta
            $correctIds = $question->choices
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values();

            $userIds = collect((array) $value)
                ->sort()
                ->values();

            if ($correctIds->all() === $userIds->all()) {
                $segments[$segName]['earned'] += $questionScore;
            }
        }

        // Convertir a etiquetas y porcentajes
        $labels = [];
        $scores = [];

        foreach ($segments as $name => $data) {
            $labels[] = $name;
            $scores[] = $data['max'] > 0
                ? round(($data['earned'] / $data['max']) * 100, 2)
                : 0;
        }

        return [
            'labels' => $labels,
            'scores' => $scores,
        ];
    }
    public function preview(Exam $exam): RedirectResponse|View
    {
        $user = auth()->user();

        $hasAttempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->exists();

        if ($hasAttempt) {
            return redirect()->route('exam.start', $exam->id);
        }

        return view('exams.prev-start', compact('exam'));
    }
}
