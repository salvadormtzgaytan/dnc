<?php

namespace App\Console\Commands;

use App\Models\Dnc;
use Illuminate\Console\Command;

class CheckDncPeriods extends Command
{
    protected $signature = 'dnc:check-periods {dnc_id}';
    protected $description = 'Verifica los períodos de una DNC específica';

    public function handle()
    {
        $dncId = $this->argument('dnc_id');
        $dnc = Dnc::with('periods')->find($dncId);

        if (!$dnc) {
            $this->error("DNC con ID {$dncId} no encontrada.");
            return 1;
        }

        $this->info("DNC: {$dnc->name} (ID: {$dnc->id})");
        $this->info("Fechas actuales:");
        $this->line("  Inicio: {$dnc->start_date}");
        $this->line("  Fin: {$dnc->end_date}");
        $this->newLine();

        $this->info("Períodos registrados: {$dnc->periods->count()}");
        $this->newLine();

        foreach ($dnc->periods as $period) {
            $current = $period->is_current ? '✓ ACTUAL' : '';
            $this->line("Período ID: {$period->id} {$current}");
            $this->line("  Nombre: {$period->period_name}");
            $this->line("  Inicio: {$period->start_date}");
            $this->line("  Fin: {$period->end_date}");
            $this->line("  Creado: {$period->created_at}");
            $this->newLine();
        }

        return 0;
    }
}
