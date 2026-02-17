<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'question_bank_id',
        'title',
        'text',
        'type',
        'default_score',
        'shuffle_choices',
        'catalog_segment_id',
        'catalog_level_id',
    ];
    
    protected $casts = [
        'shuffle_choices' => 'boolean',
    ];
    public function choices()
    {
        return $this->hasMany(QuestionChoice::class);
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public const TYPES = [
        'single' => 'Respuesta única',
        'multiple' => 'Respuesta múltiple',
    ];
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_question');
    }

    public function category()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
    public function segment()
    {
        return $this->belongsTo(CatalogSegment::class, 'catalog_segment_id');
    }

    public function level()
    {
        return $this->belongsTo(CatalogLevel::class, 'catalog_level_id');
    }
}
