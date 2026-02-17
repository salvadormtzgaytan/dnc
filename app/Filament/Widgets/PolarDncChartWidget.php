<?php

namespace App\Filament\Widgets;

use App\Models\Dnc;
use App\Models\ExamAttempt;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PolarDncChartWidget extends ChartWidget
{
    protected static ?string $heading   = 'Radar de conocimientos';
    protected static ?int $sort         = 4;
    protected static ?string $maxHeight = '500px';

    protected array $palette = [
        '#DB2A2D', '#FF8C42', '#1A365D', '#FFED78', '#5E2C82',
        '#0033A0', '#E04F00', '#FFD100', '#0091D5', '#00A887',
        
    ];

    protected ?string $activeColor = null;
    
    public ?int $periodId = null;
    
    protected $listeners = ['periodFilterChanged' => 'updatePeriodFilter'];
    
    public function updatePeriodFilter($periodId): void
    {
        $this->periodId = $periodId;
    }

    protected function getFilters(): ?array
    {
        $query = Dnc::where('is_active', true);
        
        if ($this->periodId) {
            $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
            if ($selectedPeriod) {
                $dncIds = \App\Models\DncPeriod::query()
                    ->where('start_date', $selectedPeriod->start_date)
                    ->where('end_date', $selectedPeriod->end_date)
                    ->pluck('dnc_id')
                    ->toArray();
                $query->whereIn('id', $dncIds);
            }
        }
        
        return $query->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function getType(): string
    {
        return 'radar';
    }

    public function mount(): void
    {
        $this->periodId = \App\Models\DncPeriod::where('is_current', true)->value('id');
        
        $query = Dnc::where('is_active', true);
        
        if ($this->periodId) {
            $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
            if ($selectedPeriod) {
                $dncIds = \App\Models\DncPeriod::query()
                    ->where('start_date', $selectedPeriod->start_date)
                    ->where('end_date', $selectedPeriod->end_date)
                    ->pluck('dnc_id')
                    ->toArray();
                $query->whereIn('id', $dncIds);
            }
        }
        
        $this->filter = $this->filter ?? $query->orderBy('name')->value('id');
    }

    protected function getData(): array
    {
        $dncId = $this->filter;

        if (!$dncId) {
            return ['datasets' => [], 'labels' => []];
        }

        $dnc = Dnc::with(['exams', 'assignedUsers'])->find($dncId);
        if (!$dnc) {
            return ['datasets' => [], 'labels' => []];
        }

        $this->activeColor = $this->getColorForDnc($dncId);

        // Obtener período seleccionado
        $periodIds = null;
        if ($this->periodId) {
            $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
            if ($selectedPeriod) {
                $periodIds = \App\Models\DncPeriod::query()
                    ->where('dnc_id', $dnc->id)
                    ->where('start_date', $selectedPeriod->start_date)
                    ->where('end_date', $selectedPeriod->end_date)
                    ->pluck('id')
                    ->toArray();
            }
        }

        // Obtener usuarios asignados en el período
        $userIds = $dnc->assignedUsers->pluck('id');
        
        if ($this->periodId && $selectedPeriod) {
            $userIds = DB::table('dnc_user_assignments')
                ->where('dnc_id', $dnc->id)
                ->where('created_at', '>=', $selectedPeriod->start_date)
                ->where('created_at', '<=', $selectedPeriod->end_date)
                ->pluck('user_id');
        }

        $attemptsQuery = ExamAttempt::query()
            ->whereIn('exam_id', $dnc->exams->pluck('id'))
            ->whereIn('user_id', $userIds)
            ->whereNotNull('score');
        
        if ($periodIds) {
            $attemptsQuery->whereIn('dnc_period_id', $periodIds);
        }
        
        $attempts = $attemptsQuery->get()->groupBy('exam_id');

        $labels = [];
        $data   = [];
        $pointColors = [];
        foreach ($attempts as $examId => $group) {
            $avgScore = round($group->avg('score'), 2);
            $examName = $group->first()->exam->name ?? 'Examen';

            $labels[] = $examName;
            $data[]   = $avgScore;
            $pointColors[] = $this->getColorForExam($examId);
        }

        return [
            'datasets' => [
                [
                    'label'                => 'Promedio',
                    'data'                 => $data,
                    'backgroundColor'      => $this->hexToRgba($this->activeColor, 0.2),
                    'borderColor'          => $this->activeColor,
                    'borderWidth'          => 2,
                    'pointBackgroundColor' => $pointColors,
                    'pointRadius'          => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Genera opciones del gráfico (dinámicas).
     *
     * @return array<string, mixed>|RawJs|null
     */
    protected function getOptions(): array | RawJs | null
    {
        return RawJs::make(<<<'JS'
        {
            responsive: true,
            scales: {
                y: {
                    grid: { display: false },
                    ticks: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { display: false }
                },
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        display: true,
                        stepSize: 20,
                        backdropColor: 'rgba(255, 255, 255, 0.75)',
                        color: '#6b7280',
                        font: { size: 14 },
                    },
                    pointLabels: {
                        display: true,
                        font: { size: 14 },
                        color: '#374151',
                        callback: function(label) {
                        return label.length > 10 ? label.substring(0, 10) + '…' : label;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const score = context.raw;
                            const label = context.label;
                            let level = 'Sin nivel';

                            if (score <= 50) {
                                level = 'Crítico';
                            } else if (score <= 75) {
                                level = 'Aceptable';
                            } else {
                                level = 'Óptimo';
                            }

                            return `${label}: ${score}% – ${level}`;
                        }
                    }
                }
            }
        }
    JS);
    }


    protected function getColorForDnc(int $dncId): string
    {
        return $this->palette[$dncId % count($this->palette)];
    }

    protected function hexToRgba(string $hex, float $opacity): string
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba($r, $g, $b, $opacity)";
    }

    protected function getColorForExam(int $examId): string
{
    return $this->palette[$examId % count($this->palette)];
}

}
