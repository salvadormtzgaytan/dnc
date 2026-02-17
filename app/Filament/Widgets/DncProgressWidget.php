<?php

namespace App\Filament\Widgets;
use App\Models\Dnc;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets\Concerns\CanPoll;

class DncProgressWidget extends Widget
{
    use CanPoll;

    protected static string $view = 'filament.widgets.dnc-progress-widget';

    protected static ?string $heading = 'Avance de DNCs activas';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = null;
    
    public ?int $periodId = null;

    protected $listeners = ['periodFilterChanged' => 'updatePeriodFilter'];

    public function mount(): void
    {
        $this->filter = $this->filter ?? '';
        $this->periodId = \App\Models\DncPeriod::where('is_current', true)->value('id');
    }
    
    public function updatePeriodFilter($periodId): void
    {
        $this->periodId = $periodId;
    }

    /**
     * Obtiene una colección de DNCs activas con sus métricas de avance y estado de exámenes.
     */
    public function getDncData(?string $dealershipName = null): Collection
    {
        $query = Dnc::where('is_active', true);
        
        if ($this->periodId) {
            $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
            if ($selectedPeriod) {
                // Obtener todas las DNCs que tienen un período con las mismas fechas
                $dncIds = \App\Models\DncPeriod::query()
                    ->where('start_date', $selectedPeriod->start_date)
                    ->where('end_date', $selectedPeriod->end_date)
                    ->pluck('dnc_id')
                    ->toArray();
                
                $query->whereIn('id', $dncIds);
            }
        }
        
        return $query->with(['assignedUsers.profile.dealership', 'exams'])
            ->get()
            ->map(function (Dnc $dnc) use ($dealershipName) {
                $statusCounts = $this->getFilteredExamStatusCounts($dnc, $dealershipName);

                $total = $statusCounts['completed'] + $statusCounts['in_progress'] + $statusCounts['not_started'];
                $progress = $total > 0 ? round(($statusCounts['completed'] / $total) * 100, 2) : 0;

                return [
                    'name'         => $dnc->name,
                    'progress'     => $progress,
                    'completed'    => $statusCounts['completed'],
                    'in_progress'  => $statusCounts['in_progress'],
                    'not_started'  => $statusCounts['not_started'],
                    'total'        => $total,
                ];
            })
            ->filter(function ($dncData) {
                return $dncData['total'] > 0; // Solo mostrar DNCs con usuarios asignados
            });
    }

    /**
     * Calcula conteos de estado de exámenes filtrados por concesionaria
     * Usa la misma lógica que el modelo Dnc pero con filtro
     */
    private function getFilteredExamStatusCounts(Dnc $dnc, ?string $dealershipName = null): array
    {
        // Obtener el período de esta DNC específica
        $selectedPeriod = null;
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
        $userQuery = DB::table('dnc_user_assignments')
            ->join('users', 'dnc_user_assignments.user_id', '=', 'users.id')
            ->where('dnc_user_assignments.dnc_id', $dnc->id)
            ->when($dealershipName, function ($query) use ($dealershipName) {
                $query->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                      ->join('catalog_dealerships', 'user_profiles.dealership_id', '=', 'catalog_dealerships.id')
                      ->where('catalog_dealerships.name', $dealershipName);
            });
        
        if ($selectedPeriod) {
            $userQuery->where('dnc_user_assignments.created_at', '>=', $selectedPeriod->start_date)
                      ->where('dnc_user_assignments.created_at', '<=', $selectedPeriod->end_date);
        }
        
        $userIds = $userQuery->pluck('users.id');

        // Contar usuarios únicos por estado
        $completed = 0;
        $inProgress = 0;
        $notStarted = 0;

        foreach ($userIds as $userId) {
            $hasCompleted = DB::table('exam_attempts')
                ->where('user_id', $userId)
                ->when($periodIds, fn($q) => $q->whereIn('dnc_period_id', $periodIds))
                ->where('status', 'completed')
                ->exists();

            if ($hasCompleted) {
                $completed++;
                continue;
            }

            $hasInProgress = DB::table('exam_attempts')
                ->where('user_id', $userId)
                ->when($periodIds, fn($q) => $q->whereIn('dnc_period_id', $periodIds))
                ->where('status', 'in_progress')
                ->exists();

            if ($hasInProgress) {
                $inProgress++;
                continue;
            }

            $notStarted++;
        }

        return [
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
        ];
    }

    /**
     * Obtiene las concesionarias disponibles en el período seleccionado
     */
    public function getAvailableDealerships(): array
    {
        if (!$this->periodId) {
            return \App\Models\CatalogDealership::distinct()->orderBy('name')->pluck('name', 'name')->toArray();
        }

        $selectedPeriod = \App\Models\DncPeriod::find($this->periodId);
        if (!$selectedPeriod) {
            return [];
        }

        // Obtener DNCs del período
        $dncIds = \App\Models\DncPeriod::query()
            ->where('start_date', $selectedPeriod->start_date)
            ->where('end_date', $selectedPeriod->end_date)
            ->pluck('dnc_id')
            ->toArray();

        // Obtener concesionarias de usuarios asignados en el período
        $dealerships = DB::table('dnc_user_assignments')
            ->join('users', 'dnc_user_assignments.user_id', '=', 'users.id')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('catalog_dealerships', 'user_profiles.dealership_id', '=', 'catalog_dealerships.id')
            ->whereIn('dnc_user_assignments.dnc_id', $dncIds)
            ->where('dnc_user_assignments.created_at', '>=', $selectedPeriod->start_date)
            ->where('dnc_user_assignments.created_at', '<=', $selectedPeriod->end_date)
            ->distinct()
            ->orderBy('catalog_dealerships.name')
            ->pluck('catalog_dealerships.name', 'catalog_dealerships.name')
            ->toArray();

        return $dealerships;
    }

    /**
     * Pasa los datos a la vista Blade del widget.
     */
    protected function getViewData(): array
    {
        $dealershipName = $this->filter && $this->filter !== '' ? $this->filter : null;
        
        return [
            'dncs' => $this->getDncData($dealershipName),
        ];
    }
}
