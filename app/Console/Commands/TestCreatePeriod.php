<?php

namespace App\Console\Commands;

use App\Models\Dnc;
use Illuminate\Console\Command;

class TestCreatePeriod extends Command
{
    protected $signature = 'dnc:test-create-period {dnc_id}';
    protected $description = 'Simula la creación de un nuevo período';

    public function handle()
    {
        $dncId = $this->argument('dnc_id');
        $dnc = Dnc::find($dncId);

        if (!$dnc) {
            $this->error("DNC no encontrada");
            return 1;
        }

        $this->info("Estado ANTES:");
        $this->line("Períodos: " . $dnc->periods()->count());
        $current = $dnc->currentPeriod;
        if ($current) {
            $this->line("Período actual ID: {$current->id}");
            $this->line("Fechas: {$current->start_date} - {$current->end_date}");
        }
        $this->newLine();

        // Simular cambio de fechas
        $dnc->end_date = now()->addDays(30);
        
        // Simular request con create_new_period=true
        request()->merge(['create_new_period' => true]);
        
        $this->info("Guardando con create_new_period=true...");
        $dnc->save();
        
        $this->newLine();
        $this->info("Estado DESPUÉS:");
        $dnc->refresh();
        $this->line("Períodos: " . $dnc->periods()->count());
        $current = $dnc->currentPeriod;
        if ($current) {
            $this->line("Período actual ID: {$current->id}");
            $this->line("Fechas: {$current->start_date} - {$current->end_date}");
        }

        return 0;
    }
}
