<?php

namespace App\Filament\Widgets;

use App\Models\DncPeriod;
use Filament\Widgets\Widget;

class GlobalFiltersWidget extends Widget
{
    protected static string $view = 'filament.widgets.global-filters-widget';
    
    protected static ?int $sort = 0;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?int $selectedPeriod = null;

    public function mount(): void
    {
        $this->selectedPeriod = DncPeriod::where('is_current', true)->value('id');
    }

    public function updatedSelectedPeriod($value): void
    {
        $this->dispatch('periodFilterChanged', periodId: $value);
    }

    public function getPeriods(): array
    {
        $periods = DncPeriod::query()
            ->with('dnc')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->groupBy(function($period) {
                return $period->start_date->format('Y-m-d') . '|' . $period->end_date->format('Y-m-d');
            });
        
        $result = [];
        
        foreach ($periods as $dateKey => $periodGroup) {
            $firstPeriod = $periodGroup->first();
            $periodName = $firstPeriod->period_name ?: $firstPeriod->generatePeriodName();
            $dncCount = $periodGroup->count();
            $label = $periodName . ($dncCount > 1 ? " ({$dncCount} DNCs)" : '');
            $result[$firstPeriod->id] = $label;
        }
        
        return $result;
    }
}
