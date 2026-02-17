<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\QuestionChoice;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'user_id',
        'started_at',
        'finished_at',
        'status',
        'score',
        'max_score',
        'question_order',
        'answers',
        'choice_order',
        'notified_at',
        'dnc_period_id'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'notified_at' => 'datetime',
        'question_order' => 'array',
        'answers' => 'array',
        'resumed_at' => 'datetime',
        'active_duration' => 'integer',
        'choice_order' => 'array',
    ];

    /**
     * Relación: este intento pertenece a un examen.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Relación: este intento pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: este intento pertenece a un período de DNC.
     */
    public function dncPeriod()
    {
        return $this->belongsTo(DncPeriod::class);
    }

    /**
     * Accesor: tiempo transcurrido del intento.
     * Si está finalizado → finished_at - started_at
     * Si está en progreso → now() - started_at
     */
    public function getElapsedTimeAttribute(): string
    {
        if (!$this->started_at) {
            return '—';
        }

        $end = $this->finished_at ?? now();
        $diffInSeconds = $end->diffInSeconds($this->started_at);

        return CarbonInterval::seconds($diffInSeconds)
            ->cascade()
            ->forHumans([
                'short' => true,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]);
    }

    public function getLastPageReachedAttribute(): int
    {
        if (!$this->exam || empty($this->question_order)) {
            return 1;
        }

        $questionsPerPage = $this->exam->questions_per_page ?? 1;

        // Obtener IDs de preguntas contestadas
        $answeredIds = collect($this->answers ?? [])
            ->keys()
            ->map(fn($key) => (int) str_replace('question_', '', $key))
            ->filter();

        // Obtener índice (posición) de la última pregunta contestada en el orden actual
        $lastAnsweredIndex = collect($this->question_order)
            ->filter(fn($id) => $answeredIds->contains($id))
            ->keys()
            ->last();

        return is_null($lastAnsweredIndex)
            ? 1
            : intval(floor($lastAnsweredIndex / $questionsPerPage)) + 1;
    }

    public function accumulateActiveTime(): void
    {
        if ($this->resumed_at) {
            $elapsed = now()->diffInSeconds($this->resumed_at);
            $this->active_duration += $elapsed;
            $this->resumed_at = now();
            $this->save();
        }
    }

    public function getRemainingTime(int $timeLimit): int
    {
        return max($timeLimit - ($this->active_duration ?? 0), 0);
    }
    public function complete(): void
    {
        // Acumula tiempo si está en sesión activa
        if ($this->resumed_at) {
            $this->accumulateActiveTime();
        }

        $this->status = 'completed';
        $this->finished_at = now();
        $this->resumed_at = null;

        // Sincronizar respuestas antes de guardar
        $this->syncAnswersToTable();

        $this->save();
    }


    public function scopeBestAttempts($query)
    {
        return $query->select('exam_attempts.*')
            ->join(DB::raw('(
            SELECT user_id, exam_id, MAX(score) as max_score
            FROM exam_attempts
            GROUP BY user_id, exam_id
        ) as best'), function ($join) {
                $join->on('exam_attempts.user_id', '=', 'best.user_id')
                    ->on('exam_attempts.exam_id', '=', 'best.exam_id')
                    ->on('exam_attempts.score', '=', 'best.max_score');
            });
    }

    public function getCorrectCount(): int
    {
        $correct = 0;

        foreach ($this->answers ?? [] as $key => $value) {
            $questionId = (int) str_replace('question_', '', $key);

            $correctChoiceIds = QuestionChoice::query()
                ->where('question_id', $questionId)
                ->where('is_correct', true)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            $userChoiceIds = collect((array) $value)
                ->sort()
                ->values()
                ->toArray();

            if ($correctChoiceIds === $userChoiceIds) {
                $correct++;
            }
        }

        return $correct;
    }

    public function getIncorrectCount(): int
    {
        return count($this->answers ?? []) - $this->getCorrectCount();
    }

    public function durationFormatted(): string
    {
        if (!$this->started_at || !$this->finished_at) {
            return '—';
        }

        $diffInSeconds = $this->finished_at->diffInSeconds($this->started_at);

        return CarbonInterval::seconds($diffInSeconds)
            ->cascade()
            ->forHumans([
                'short' => true,
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
            ]);
    }

    public function answersRel()
    {
        return $this->hasMany(ExamAttemptAnswer::class, 'attempt_id');
    }

    public function syncAnswersToTable(): void
    {
        if (empty($this->answers)) {
            return;
        }

        // Primero eliminamos todas las respuestas existentes
        $this->answersRel()->delete();

        // Luego creamos los nuevos registros
        foreach ($this->answers as $key => $value) {
            $questionId = (int) str_replace('question_', '', $key);
            $question = Question::find($questionId);

            if (!$question) continue;

            $correctChoiceIds = $question->choices()
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();

            $selectedChoiceIds = is_array($value) ? $value : [$value];

            foreach ($selectedChoiceIds as $selectedChoiceId) {
                ExamAttemptAnswer::create([
                    'attempt_id' => $this->id,
                    'question_id' => $questionId,
                    'selected_choice_id' => $selectedChoiceId,
                    'correct_choice_id' => $correctChoiceIds[0] ?? null,
                ]);
            }
        }
    }
}
