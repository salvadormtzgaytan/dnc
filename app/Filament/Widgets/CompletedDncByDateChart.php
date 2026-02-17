<?php

namespace App\Filament\Widgets;

use App\Models\Dnc;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompletedDncByDateChart extends LineChartWidget
{
    protected static ?int $sort = 6;
    /**
     * Título del widget.
     *
     * @var string|null
     */
    protected static ?string $heading = 'Exámenes completados diarios por DNC';

    /**
     * Filtro por defecto al cargar: 'month'.
     *
     * @var string|null
     */
    protected static ?string $defaultFilter = 'month';


    /**
     * Define los filtros disponibles.
     *
     * @return array<string, string>|null
     */
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hoy',
            'week'  => 'Última semana',
            'month' => 'Último mes',
            'year'  => 'Este año',
        ];
    }

    /**
     * Genera los datos del gráfico según el filtro activo.
     *
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $now = Carbon::now();

        // 1) Definir fecha de inicio según filtro
        switch ($this->filter) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $days  = 1;
                break;
            case 'week':
                $start = $now->copy()->subDays(6)->startOfDay();
                $days  = 7;
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $days  = $now->dayOfYear;
                break;
            case 'month':
            default:
                $start = $now->copy()->subDays(29)->startOfDay();
                $days  = 30;
                break;
        }

        // 2) Generar arrays de fechas y etiquetas
        $dates = collect(range(0, $days - 1))
            ->map(fn($i) => $start->copy()->addDays($i)->format('Y-m-d'))
            ->toArray();

        $labels = array_map(
            fn($d) => Carbon::createFromFormat('Y-m-d', $d)->format('d/m'),
            $dates
        );

        // 3) Consulta: conteo de exámenes completados por DNC y fecha
        $raw = DB::table('exam_attempts')
            ->where('status', 'completed')
            ->whereBetween('finished_at', [$start, $now])
            ->join('exams', 'exam_attempts.exam_id', '=', 'exams.id')
            ->join('dnc_exam', 'exams.id', '=', 'dnc_exam.exam_id')
            ->join('dncs', 'dnc_exam.dnc_id', '=', 'dncs.id')
            ->select(
                'dncs.name as label',
                DB::raw('DATE(finished_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('label', 'date')
            ->orderBy('label')
            ->orderBy('date')
            ->get();

        // 4) Pivoteo de resultados
        $counts = [];
        foreach ($raw as $row) {
            $counts[$row->label][$row->date] = (int) $row->total;
        }

        // 5) Todos los nombres de DNC
        $dncNames = Dnc::pluck('name')->toArray();

        // 6) Paleta de colores
        $colors = [
            '#3b82f6',
            '#10b981',
            '#f97316',
            '#ef4444',
            '#eab308',
            '#8b5cf6',
            '#0ea5e9',
            '#f43f5e',
        ];

        // 7) Construir datasets
        $datasets = [];
    foreach ($dncNames as $index => $name) {
        $data = [];
        foreach ($dates as $date) {
            $data[] = $counts[$name][$date] ?? 0;
        }
        $color = $colors[$index % count($colors)];
        $datasets[] = [
            'label'           => $name,
            'data'            => $data,
            'borderColor'     => $color,
            'backgroundColor' => $color . '33', // Color con transparencia para el área
            'fill'            => 'origin', // Esto coloreará desde la línea hasta el eje Y=0
            'tension'         => 0.4,
            'pointRadius'     => 4,
        ];
    }

    return [
        'labels'   => $labels,
        'datasets' => $datasets,
    ];
    }

    /**
     * Opciones extra para Chart.js.
     *
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'stepSize'  => 1,
                        'precision' => 0,
                        'color'     => '#4b5563',
                    ],
                    'grid'        => [
                        'color'      => 'rgba(203,213,225,0.5)',
                        'borderDash' => [4, 4],
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'color' => '#4b5563',
                    ],
                ],
            ],
            'plugins' => [
                'legend'  => ['position' => 'top'],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0,0,0,0.7)',
                    'titleColor'      => '#ffffff',
                    'bodyColor'       => '#f3f4f6',
                    'borderColor'     => '#374151',
                    'borderWidth'     => 1,
                ],
            ],
        ];
    }
}
