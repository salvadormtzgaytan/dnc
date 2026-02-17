<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamFeedback extends Model
{
    use HasFactory;
    protected $fillable = ['exam_id', 'min_score', 'max_score', 'feedback'];
    
    protected $casts = [
        'min_score' => 'decimal:2', // Especifica 2 decimales
        'max_score' => 'decimal:2', // Especifica 2 decimales
    ];

    protected static function booted()
    {
        static::saving(function ($feedback) {
            // Validar que min no sea mayor que max
            if ($feedback->min_score > $feedback->max_score) {
                throw ValidationException::withMessages([
                    'max_score' => 'El valor máximo debe ser mayor o igual al mínimo.',
                ]);
            }

            // Validar superposición con otros rangos
            $exists = self::where('exam_id', $feedback->exam_id)
                ->where('id', '!=', $feedback->id ?? null)
                ->where(function ($query) use ($feedback) {
                    $query->where(function ($q) use ($feedback) {
                        $q->where('min_score', '<=', $feedback->min_score)
                          ->where('max_score', '>=', $feedback->min_score);
                    })->orWhere(function ($q) use ($feedback) {
                        $q->where('min_score', '<=', $feedback->max_score)
                          ->where('max_score', '>=', $feedback->max_score);
                    })->orWhere(function ($q) use ($feedback) {
                        $q->where('min_score', '>=', $feedback->min_score)
                          ->where('max_score', '<=', $feedback->max_score);
                    });
                })
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'min_score' => 'Este rango se superpone con otra retroalimentación existente.',
                    'max_score' => 'Este rango se superpone con otra retroalimentación existente.',
                ]);
            }
        });
    }
}
