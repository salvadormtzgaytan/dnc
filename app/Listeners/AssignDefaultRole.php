<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AssignDefaultRole implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Maneja el evento Registered.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Solo asigna el rol si no tiene ninguno de los definidos
        if (! $user->hasAnyRole(['participante'])) {
            $user->assignRole('participante');
        }
    }
}
