<?php

namespace App\Utils;

class ScoreColorHelper
{
    public const SCORE_RANGES = [
        'critical' => [
            'min' => 0,
            'max' => 50.99,
            'color' => 'danger',
            'class' => 'badge-error',
            'level' => 'Crítico'
        ],
        'acceptable' => [
            'min' => 51,
            'max' => 75.99,
            'color' => 'warning',
            'class' => 'badge-warning',
            'level' => 'Aceptable'
        ],
        'optimal' => [
            'min' => 76,
            'max' => 100,
            'color' => 'success',
            'class' => 'badge-success',
            'level' => 'Óptimo'
        ],
    ];

    /**
     * Retorna el color de acuerdo al puntaje.
     *
     * @param float|int|null $score
     * @return string
     */
    public static function forScore(float|int|null $score): string
    {
        return self::getScoreAttribute($score, 'color', 'gray');
    }

    /**
     * Retorna el nivel textual según el puntaje.
     *
     * @param float|int|null $score
     * @return string
     */
    public static function level(float|int|null $score): string
    {
        return self::getScoreAttribute($score, 'level', 'Sin nivel');
    }

    /**
     * Retorna la clase CSS de DaisyUI según el puntaje.
     *
     * @param float|int|null $score
     * @return string
     */
    public static function daisyBadgeClass(float|int|null $score): string
    {
        return self::getScoreAttribute($score, 'class', 'badge-neutral');
    }

    /**
     * Método privado para determinar el atributo del puntaje.
     *
     * @param float|int|null $score
     * @param string $attribute
     * @param string $default
     * @return string
     */
    private static function getScoreAttribute(float|int|null $score, string $attribute, string $default): string
    {
        if (is_null($score)) {
            return $default;
        }

        // Asegurar que el score esté entre 0 y 100
        $score = max(0, min(100, (float) $score));

        foreach (self::SCORE_RANGES as $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $range[$attribute];
            }
        }

        return $default;
    }
}
