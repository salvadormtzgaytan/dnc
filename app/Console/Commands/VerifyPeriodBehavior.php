<?php

namespace App\Console\Commands;

use App\Models\Dnc;
use Illuminate\Console\Command;

class VerifyPeriodBehavior extends Command
{
    protected $signature = 'dnc:verify-period-behavior {dnc_id}';
    protected $description = 'Verifica que actualización y creación de períodos funcionen correctamente';

    public function handle()
    {
        $dncId = $this->argument('dnc_id');
        $dnc = Dnc::find($dncId);

        if (!$dnc) {
            $this->error("DNC no encontrada");
            return 1;
        }

        $this->info("=== PRUEBA 1: ACTUALIZAR PERÍODO ACTUAL ===");
        $this->newLine();
        
        $initialCount = $dnc->periods()->count();
        $currentPeriod = $dnc->currentPeriod;
        
        $this->line("Estado inicial:");
        $this->line("  Total períodos: {$initialCount}");
        $this->line("  Período actual ID: {$currentPeriod->id}");
        $this->line("  Fechas actuales: {$currentPeriod->start_date} - {$currentPeriod->end_date}");
        $this->newLine();

        // Simular actualización (update_current_period = true)
        $dnc->end_date = now()->addDays(45);
        request()->merge(['create_new_period' => false]); // Actualizar
        $dnc->save();
        
        $dnc->refresh();
        $afterUpdateCount = $dnc->periods()->count();
        $afterUpdateCurrent = $dnc->currentPeriod;
        
        $this->line("Después de actualizar:");
        $this->line("  Total períodos: {$afterUpdateCount}");
        $this->line("  Período actual ID: {$afterUpdateCurrent->id}");
        $this->line("  Fechas actuales: {$afterUpdateCurrent->start_date} - {$afterUpdateCurrent->end_date}");
        $this->newLine();

        if ($afterUpdateCount === $initialCount && $afterUpdateCurrent->id === $currentPeriod->id) {
            $this->info("✓ ACTUALIZACIÓN: Correcto - No se creó nuevo período, se actualizó el existente");
        } else {
            $this->error("✗ ACTUALIZACIÓN: Incorrecto - Se creó un nuevo período cuando debía actualizar");
        }

        $this->newLine();
        $this->info("=== PRUEBA 2: CREAR NUEVO PERÍODO ===");
        $this->newLine();

        $beforeCreateCount = $dnc->periods()->count();
        $beforeCreateCurrent = $dnc->currentPeriod;
        
        $this->line("Estado antes de crear:");
        $this->line("  Total períodos: {$beforeCreateCount}");
        $this->line("  Período actual ID: {$beforeCreateCurrent->id}");
        $this->newLine();

        // Simular creación (update_current_period = false)
        $dnc->end_date = now()->addDays(60);
        request()->merge(['create_new_period' => true]); // Crear nuevo
        $dnc->save();
        
        $dnc->refresh();
        $afterCreateCount = $dnc->periods()->count();
        $afterCreateCurrent = $dnc->currentPeriod;
        
        $this->line("Después de crear:");
        $this->line("  Total períodos: {$afterCreateCount}");
        $this->line("  Período actual ID: {$afterCreateCurrent->id}");
        $this->line("  Fechas actuales: {$afterCreateCurrent->start_date} - {$afterCreateCurrent->end_date}");
        $this->newLine();

        if ($afterCreateCount === $beforeCreateCount + 1 && $afterCreateCurrent->id !== $beforeCreateCurrent->id) {
            $this->info("✓ CREACIÓN: Correcto - Se creó un nuevo período");
        } else {
            $this->error("✗ CREACIÓN: Incorrecto - No se creó nuevo período");
        }

        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->line("Períodos iniciales: {$initialCount}");
        $this->line("Períodos finales: {$afterCreateCount}");
        $this->line("Períodos creados: " . ($afterCreateCount - $initialCount));

        return 0;
    }
}
