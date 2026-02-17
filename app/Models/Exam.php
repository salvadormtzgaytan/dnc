<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    public const NAVIGATION_METHODS = [
        'sequential' => 'Secuencial',
        'free'       => 'Libre',
    ];
    protected $fillable = [
        'name',
        'image_path',
        'start_at',
        'end_at',
        'time_limit',
        'max_attempts',
        'pass_score',
        'max_score',
        'questions_per_page',
        'navigation_method',
        'enabled',
        'shuffle_questions',
    ];

    protected $casts = [
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
        'time_limit'        => 'integer',
        'shuffle_questions' => 'boolean',
    ];
    public function feedbacks()
    {
        return $this->hasMany(ExamFeedback::class);
    }
    public function getTimeLimitFormattedAttribute(): string
    {
        $timeLimit = $this->time_limit ?? $this->exam?->time_limit;

        if (is_null($timeLimit)) {
            return 'Sin límite';
        }

        $hours   = floor($timeLimit / 3600);
        $minutes = floor(($timeLimit % 3600) / 60);
        $seconds = $timeLimit % 60;

        return ($this->time_limit !== null ? '' : 'Por defecto ') . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    // Mutador para guardar el tiempo desde hh:mm:ss
    public function setTimeLimitAttribute($value)
    {
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $value)) {
            [$h, $m, $s]                    = explode(':', $value);
            $this->attributes['time_limit'] = ($h * 3600) + ($m * 60) + $s;
        } else {
            $this->attributes['time_limit'] = $value;
        }
    }

    public function userOverrides()
    {
        return $this->hasMany(ExamUserOverride::class);
    }
    public function questions()
    {
        return $this->belongsToMany(Question::class, 'exam_question');
    }
    public function questionBank()
    {
        return $this->hasOne(QuestionBank::class);
    }

    protected static function booted()
    {
        static::created(function (Exam $exam) {
            QuestionBank::create([
                'name'    => 'Banco para Examen: ' . $exam->name,
                'exam_id' => $exam->id,
            ]);
        });
    }

    public function dncs()
    {
        return $this->belongsToMany(Dnc::class, 'dnc_exam');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

}
