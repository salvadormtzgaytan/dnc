<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dnc extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'bcc_emails',
        'start_date',
        'end_date',
        'is_active',
        'image_path',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'dnc_exam');
    }
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'dnc_user_assignments')
            ->withTimestamps();
    }

    public function userOverrides()
    {
        return $this->hasMany(DncUserOverride::class);
    }

    public function isExpired(): bool
    {
        $now = now();

        return ($this->start_date && $now->lt($this->start_date))
            || ($this->end_date && $now->gt($this->end_date));
    }

    public function isExpiredForUser($userId): bool
    {
        $override = $this->userOverrides()->where('user_id', $userId)->first();
        
        if ($override) {
            $now = now();
            $start = $override->custom_start_date ?? $this->start_date;
            $end = $override->custom_end_date ?? $this->end_date;
            
            return ($start && $now->lt($start)) || ($end && $now->gt($end));
        }
        
        return $this->isExpired();
    }
    public function thresholds()
    {
        return $this->hasMany(DncNotificationThreshold::class)
            ->orderBy('days_before', 'desc');
    }

    public function periods()
    {
        return $this->hasMany(DncPeriod::class)->orderBy('start_date', 'desc');
    }

    public function currentPeriod()
    {
        return $this->hasOne(DncPeriod::class)->where('is_current', true);
    }

    /**
     * Crea un nuevo período cuando se actualizan las fechas de la DNC
     */
    public function createNewPeriod()
    {
        // Marcar el período actual como no actual
        $this->periods()->update(['is_current' => false]);

        // Crear nuevo período
        $period = $this->periods()->create([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_current' => true
        ]);

        // Generar nombre automático si no se proporciona
        if (!$period->period_name) {
            $period->update(['period_name' => $period->generatePeriodName()]);
        }

        return $period;
    }

    /**
     * Obtiene el período actual o lo crea si no existe
     */
    public function getCurrentOrCreatePeriod()
    {
        $current = $this->currentPeriod;
        
        if (!$current) {
            return $this->createNewPeriod();
        }

        // Si las fechas cambiaron, crear nuevo período
        if ($current->start_date != $this->start_date || $current->end_date != $this->end_date) {
            return $this->createNewPeriod();
        }

        return $current;
    }

    /**
     * Actualiza las fechas del período actual sin crear uno nuevo
     */
    public function updateCurrentPeriod()
    {
        $current = $this->currentPeriod;
        
        if (!$current) {
            return $this->createNewPeriod();
        }

        // Actualizar fechas del período actual
        $current->start_date = $this->start_date;
        $current->end_date = $this->end_date;
        $current->period_name = $current->generatePeriodName();
        $current->save();

        return $current;
    }

    /**
     * Calcula el número máximo de días antes de 'end_date'
     * para notificaciones, basándose en el periodo de la DNC.
     *
     */
    public function getMaxNotificationRange(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $today     = Carbon::now()->startOfDay();
        $startDate = $this->start_date->startOfDay();
        $endDate   = $this->end_date->endOfDay();

        // Si aún no hemos llegado al inicio, permitimos avisar
        // hasta end_date desde start_date.
        $baseDate = $today->greaterThan($startDate) ? $today : $startDate;

        if ($baseDate->greaterThan($endDate)) {
            // Si la DNC ya terminó, no permitimos umbrales.
            return 0;
        }

        return $baseDate->diffInDays($endDate);
    }
    /**
     * Devuelve todos los exámenes asignados a esta DNC.
     * Se utiliza como alias de exámenes totales en widgets de progreso.
     */
    public function totalExams(): BelongsToMany
    {
        return $this->exams();
    }

    /**
 * Devuelve los exámenes de esta DNC que tienen al menos un intento completado.
 * Se utiliza como alias de exámenes completados en widgets de progreso.
 */
    public function completedExams(): BelongsToMany
    {
        return $this->exams()->whereHas('attempts', function ($query) {
            $query->where('status', 'completed');
        });
    }

    
    /**
     * Calcula cuántos exámenes están completados, en progreso o sin iniciar
     * considerando cada combinación usuario-examen asignada a esta DNC.
     *
     * @return array{
     *     completed: int,
     *     in_progress: int,
     *     not_started: int
     * }
     */
    public function getExamStatusCounts(): array
    {
        $completed  = 0;
        $inProgress = 0;
        $notStarted = 0;

        $userIds = $this->assignedUsers()->pluck('users.id');
        $examIds = $this->exams()->pluck('exams.id');

        foreach ($userIds as $userId) {
            foreach ($examIds as $examId) {
                $status = \App\Models\ExamAttempt::where('user_id', $userId)
                    ->where('exam_id', $examId)
                    ->orderByDesc('finished_at')
                    ->value('status');

                if ($status === 'completed') {
                    $completed++;
                } elseif ($status === 'in_progress') {
                    $inProgress++;
                } else {
                    $notStarted++;
                }
            }
        }

        return [
            'completed'   => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
        ];
    }


}
