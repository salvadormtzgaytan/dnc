<?php

namespace App\Observers;

use App\Models\Dnc;

class DncObserver
{
    /**
     * Handle the Dnc "created" event.
     */
    public function created(Dnc $dnc): void
    {
        // Crear el primer período cuando se crea una DNC
        $dnc->createNewPeriod();
    }

    /**
     * Handle the Dnc "updated" event.
     */
    public function updated(Dnc $dnc): void
    {
        // Solo procesar si las fechas cambiaron
        if ($dnc->wasChanged(['start_date', 'end_date'])) {
            $createNew = request()->boolean('create_new_period', false);
            
            \Log::info('DncObserver updated', [
                'dnc_id' => $dnc->id,
                'create_new_period' => $createNew,
                'old_dates' => [
                    'start' => $dnc->getOriginal('start_date'),
                    'end' => $dnc->getOriginal('end_date'),
                ],
                'new_dates' => [
                    'start' => $dnc->start_date,
                    'end' => $dnc->end_date,
                ],
            ]);
            
            if ($createNew) {
                \Log::info('Creating new period');
                $dnc->createNewPeriod();
            } else {
                \Log::info('Updating current period');
                $dnc->updateCurrentPeriod();
            }
        }
    }
}