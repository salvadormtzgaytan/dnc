<?php

namespace App\Filament\Widgets;

use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\DB;

class AverageScoreByDncChart extends BarChartWidget
{
    protected static ?string $heading = 'Comparativa de Promedios por DNC';
    protected static ?int $sort = 3;
    //protected static ?string $maxHeight = '500px';

    public ?int $periodId = null;

    protected $listeners = ['periodFilterChanged' => 'updatePeriodFilter'];

    public function mount(): void
    {
        $this->periodId = \App\Models\DncPeriod::where('is_current', true)->value('id');
    }

    public function updatePeriodFilter($periodId): void
    {
        $this->periodId = $periodId;
    }

    protected function getData(): array
    {
        // Obtener período seleccionado
        $selectedPeriod = null;
        $periodIds = [];

        if ($this->periodId) {
            $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
            if ($selectedPeriod) {
                $periodIds = \App\Models\DncPeriod::query()
                    ->where('start_date', $selectedPeriod->start_date)
                    ->where('end_date', $selectedPeriod->end_date)
                    ->pluck('id')
                    ->toArray();
            }
        }

        // Filtrar DNCs por período
        $dncQuery = DB::table('dncs')->where('is_active', true);

        if ($selectedPeriod) {
            $dncIds = \App\Models\DncPeriod::query()
                ->where('start_date', $selectedPeriod->start_date)
                ->where('end_date', $selectedPeriod->end_date)
                ->pluck('dnc_id')
                ->toArray();
            $dncQuery->whereIn('id', $dncIds);
        }

        $dncs = $dncQuery->get(['id', 'name']);

        $realAverages = [];
        $simpleAverages = [];

        foreach ($dncs as $dnc) {
            // Promedio Real
            $realAvgQuery = DB::table('dnc_user_assignments as dua')
                ->join('dnc_exam as de', 'de.dnc_id', '=', 'dua.dnc_id')
                ->leftJoin('exam_attempts as ea', function($join) use ($periodIds) {
                    $join->on('ea.exam_id', '=', 'de.exam_id')
                         ->on('ea.user_id', '=', 'dua.user_id')
                         ->where('ea.status', '=', 'completed');
                    if (!empty($periodIds)) {
                        $join->whereIn('ea.dnc_period_id', $periodIds);
                    }
                })
                ->where('dua.dnc_id', $dnc->id);

            if ($selectedPeriod) {
                $realAvgQuery->where('dua.created_at', '>=', $selectedPeriod->start_date)
                             ->where('dua.created_at', '<=', $selectedPeriod->end_date);
            }

            $realAvg = $realAvgQuery->selectRaw('AVG(COALESCE(ea.score, 0)) as avg_score')
                ->value('avg_score') ?? 0;

            $realAverages[] = (object)['label' => $dnc->name, 'real_avg' => round($realAvg, 2)];

            // Promedio Simple
            $simpleAvgQuery = DB::table('exam_attempts as ea')
                ->join('dnc_exam as de', 'de.exam_id', '=', 'ea.exam_id')
                ->where('de.dnc_id', $dnc->id)
                ->where('ea.status', 'completed');

            if (!empty($periodIds)) {
                $simpleAvgQuery->whereIn('ea.dnc_period_id', $periodIds);
            }

            $simpleAvg = $simpleAvgQuery->avg('ea.score') ?? 0;

            $simpleAverages[] = (object)['label' => $dnc->name, 'simple_avg' => round($simpleAvg, 2)];
        }

        // Combinar los resultados
        $labels = collect($simpleAverages)->pluck('label')->toArray();

        $datasets = [
            [
                'label' => 'Promedio Real (incluye no realizados)',
                'data' => collect($realAverages)->pluck('real_avg')->toArray(),
                'backgroundColor' => 'rgba(59, 130, 246, 0.7)', // Azul
                'borderColor' => 'rgba(59, 130, 246, 1)',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Promedio Simple (solo completados)',
                'data' => collect($simpleAverages)->pluck('simple_avg')->toArray(),
                'backgroundColor' => 'rgba(16, 185, 129, 0.7)', // Verde
                'borderColor' => 'rgba(16, 185, 129, 1)',
                'borderWidth' => 1,
            ]
        ];

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => $this->getChartOptions()
        ];
    }

    protected function getChartOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => ['display' => true, 'text' => 'Puntuación (%)'],
                    'grid' => ['color' => 'rgba(203, 213, 225, 0.5)', 'borderDash' => [4, 4]],
                    'ticks' => ['stepSize' => 10, 'color' => '#4b5563']
                ],
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#4b5563']
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'color' => '#4b5563',
                        'font' => ['weight' => 'bold']
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0,0,0,0.8)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#f3f4f6',
                    'borderColor' => '#374151',
                    'borderWidth' => 1,
                    'callbacks' => [
                        'label' => function($context) {
                            return $context->dataset->label . ': ' . $context->raw . '%';
                        }
                    ]
                ]
            ]
        ];
    }
}
