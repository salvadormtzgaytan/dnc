<?php

namespace App\Filament\Widgets;

use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\DB;

class AverageScoreByDealershipChart extends BarChartWidget
{
    protected static ?int $sort = 99;
    /**
     * Título que aparecerá en el panel.
     *
     * @var string|null
     */
    protected static ?string $heading = 'Puntuación media por Concesionaria';

    /**
     * Construye el dataset: AVG(score) por concesionaria (top 10) basado en la relación Store → Dealership.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    protected function getData(): array
    {
        // 1) Consulta top 10 promedio de puntuaciones
        $raw = DB::table('exam_attempts')
            ->join('users', 'exam_attempts.user_id', '=', 'users.id')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            // Unir con stores y luego con dealerships
            ->join('catalog_stores', 'user_profiles.store_id', '=', 'catalog_stores.id')
            ->join('catalog_dealerships', 'catalog_stores.dealership_id', '=', 'catalog_dealerships.id')
            ->select('catalog_dealerships.name as label', DB::raw('AVG(exam_attempts.score) as value'))
            ->groupBy('catalog_dealerships.name')
            ->orderByDesc('value')
            ->limit(10)
            ->pluck('value', 'label')
            ->toArray();

        // 2) Preparar labels y datos
        $labels = array_keys($raw);
        $data   = array_map(fn($v) => round($v, 2), array_values($raw));

        // 3) Paletas de colores ajustables
        $bgPalette = [
            'rgba(59,130,246,0.5)',  // primary
            'rgba(16,185,129,0.5)',  // success
            'rgba(249,115,22,0.5)',  // warning
            'rgba(239,68,68,0.5)',   // danger
            'rgba(234,179,8,0.5)',   // accent
        ];
        $borderPalette = [
            'rgba(59,130,246,1)',
            'rgba(16,185,129,1)',
            'rgba(249,115,22,1)',
            'rgba(239,68,68,1)',
            'rgba(234,179,8,1)',
        ];

        // 4) Generar asignación cíclica de colores
        $backgrounds     = [];
        $borders         = [];
        $hoverBackground = [];
        $count = count($bgPalette);
        foreach ($data as $i => $value) {
            $idx = $i % $count;
            $backgrounds[]     = $bgPalette[$idx];
            $borders[]         = $borderPalette[$idx];
            $hoverBackground[] = str_replace('0.5', '0.8', $bgPalette[$idx]);
        }

        // 5) Estructura final para ChartJS
        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'                => 'Puntuación media',
                    'data'                 => $data,
                    'backgroundColor'      => $backgrounds,
                    'borderColor'          => $borders,
                    'borderWidth'          => 2,
                    'borderRadius'         => 4,
                    'hoverBackgroundColor' => $hoverBackground,
                ],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'tooltip' => [
                        'backgroundColor' => 'rgba(0,0,0,0.7)',
                        'titleColor'      => '#ffffff',
                        'bodyColor'       => '#f3f4f6',
                        'borderColor'     => '#374151',
                        'borderWidth'     => 1,
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => [
                            'color'      => 'rgba(203,213,225,0.5)',
                            'borderDash' => [4, 4],
                        ],
                        'ticks' => ['color' => '#4b5563'],
                    ],
                    'x' => ['ticks' => ['color' => '#4b5563']],
                ],
            ],
        ];
    }
}
