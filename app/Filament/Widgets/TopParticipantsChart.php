<?php

namespace App\Filament\Widgets;

use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Facades\DB;

class TopParticipantsChart extends BarChartWidget
{
    protected static ?int $sort = 5;
    protected static ?string $heading = 'Top 10 Participantes (Promedio Real)';
    protected int | string | array $columnSpan = 'full';
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

        // Construir subquery para máximo score
        $periodFilter = '';
        if (!empty($periodIds)) {
            $periodIdsStr = implode(',', $periodIds);
            $periodFilter = "AND dnc_period_id IN ($periodIdsStr)";
        }

        $usersQuery = DB::table('users')
            ->join('dnc_user_assignments as dua', 'dua.user_id', '=', 'users.id')
            ->join('dnc_exam as de', 'de.dnc_id', '=', 'dua.dnc_id')
            ->leftJoin(DB::raw("(
                SELECT user_id, exam_id, MAX(score) as score
                FROM exam_attempts
                WHERE status = 'completed' $periodFilter
                GROUP BY user_id, exam_id
            ) as ea_max"), function ($join) {
                $join->on('ea_max.user_id', '=', 'users.id')
                     ->on('ea_max.exam_id', '=', 'de.exam_id');
            })
            ->whereExists(function ($query) {
                $query->from('model_has_roles')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('model_has_roles.role_id', function ($sub) {
                        $sub->select('id')->from('roles')->where('name', 'participante')->limit(1);
                    });
            })
            ->select(
                'users.name as label',
                DB::raw('COALESCE(SUM(COALESCE(ea_max.score,0))/COUNT(DISTINCT de.exam_id),0) as avg_real_score')
            );

        // Filtrar por período en asignaciones
        if ($selectedPeriod) {
            $usersQuery->where('dua.created_at', '>=', $selectedPeriod->start_date)
                       ->where('dua.created_at', '<=', $selectedPeriod->end_date);
        }

        $users = $usersQuery->groupBy('users.id', 'users.name')
            ->orderByDesc('avg_real_score')
            ->limit(10)
            ->get();

        $labels = $users->pluck('label')->toArray();
        $data = $users->pluck('avg_real_score')->map(fn($v) => round($v, 2))->toArray();

        $bgPalette     = [
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

        $backgrounds      = [];
        $borders          = [];
        $hoverBackgrounds = [];

        foreach ($data as $index => $value) {
            $idx = $index % count($bgPalette);
            $backgrounds[]      = $bgPalette[$idx];
            $borders[]          = $borderPalette[$idx];
            $hoverBackgrounds[] = str_replace('0.5', '0.8', $bgPalette[$idx]);
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'                => 'Promedio Real',
                    'data'                 => $data,
                    'backgroundColor'      => $backgrounds,
                    'borderColor'          => $borders,
                    'borderWidth'          => 2,
                    'borderRadius'         => 4,
                    'hoverBackgroundColor' => $hoverBackgrounds,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 10,
                    ],
                ],
            ],
        ];
    }
}
