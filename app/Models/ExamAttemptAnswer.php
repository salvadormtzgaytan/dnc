<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAttemptAnswer extends Model
{
    use HasFactory;
    protected $table = 'exam_attempt_answers';
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_choice_id',
        'correct_choice_id',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    public function selectedChoice()
    {
        return $this->belongsTo(QuestionChoice::class, 'selected_choice_id');
    }
    public function correctChoice()
    {
        return $this->belongsTo(QuestionChoice::class, 'correct_choice_id');
    }
    /**
     * Relación con el intento de examen.
     */
    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }
}
