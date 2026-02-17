<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamUserOverride extends Model
{
    use HasFactory;
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'time_limit' => 'integer',
    ];
    protected $fillable = [
        'exam_id',
        'user_id',
        'start_at',
        'end_at',
        'max_attempts',
        'time_limit'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Accesor para mostrar formateado
    public function getTimeLimitFormattedAttribute(): string
    {
        $timeLimit = $this->time_limit ?? $this->exam?->time_limit;
    
        if (is_null($timeLimit)) {
            return 'Sin límite';
        }
    
        $hours = floor($timeLimit / 3600);
        $minutes = floor(($timeLimit % 3600) / 60);
        $seconds = $timeLimit % 60;
    
        return ($this->time_limit !== null ? '' : 'Por defecto ') . sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    // Mutador para guardar el tiempo desde hh:mm:ss
    public function setTimeLimitAttribute($value)
    {
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $value)) {
            [$h, $m, $s] = explode(':', $value);
            $this->attributes['time_limit'] = ($h * 3600) + ($m * 60) + $s;
        } else {
            $this->attributes['time_limit'] = $value;
        }
    }

}
