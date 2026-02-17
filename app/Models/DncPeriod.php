<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DncPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'dnc_id',
        'start_date',
        'end_date',
        'period_name',
        'is_current'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function dnc()
    {
        return $this->belongsTo(Dnc::class);
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Genera un nombre automático para el período basado en las fechas
     */
    public function generatePeriodName(): string
    {
        if (!$this->start_date && !$this->end_date) {
            return 'Período sin fechas';
        }

        if (!$this->start_date) {
            return 'Hasta ' . $this->end_date->format('d/m/Y');
        }

        if (!$this->end_date) {
            return 'Desde ' . $this->start_date->format('d/m/Y');
        }

        $startMonth = $this->start_date->format('M Y');
        $endMonth = $this->end_date->format('M Y');

        if ($startMonth === $endMonth) {
            return $startMonth;
        }

        return $this->start_date->format('d/m/Y') . ' - ' . $this->end_date->format('d/m/Y');
    }
}