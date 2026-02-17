<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Dnc;
use Illuminate\Console\Command;

class CheckUserOverride extends Command
{
    protected $signature = 'dnc:check-override {email}';
    protected $description = 'Verifica anulaciones y estado de DNC para un usuario';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario no encontrado: {$email}");
            return 1;
        }

        $this->info("Usuario: {$user->name} (ID: {$user->id})");
        $this->info("Email: {$user->email}");
        $this->newLine();

        $dncs = $user->dncs()->with('userOverrides')->get();

        if ($dncs->isEmpty()) {
            $this->warn('No tiene DNCs asignadas');
            return 0;
        }

        foreach ($dncs as $dnc) {
            $this->info("DNC: {$dnc->name} (ID: {$dnc->id})");
            $this->line("  Fechas DNC:");
            $this->line("    Inicio: {$dnc->start_date}");
            $this->line("    Fin: {$dnc->end_date}");
            
            $override = $dnc->userOverrides()->where('user_id', $user->id)->first();
            
            if ($override) {
                $this->warn("  ✓ TIENE ANULACIÓN:");
                $this->line("    Inicio custom: {$override->custom_start_date}");
                $this->line("    Fin custom: {$override->custom_end_date}");
                $this->line("    Razón: {$override->reason}");
            } else {
                $this->line("  Sin anulación");
            }

            $isExpired = $dnc->isExpiredForUser($user->id);
            $this->line("  Estado: " . ($isExpired ? '❌ EXPIRADO' : '✓ ACTIVO'));
            
            $now = now();
            $this->line("  Fecha actual: {$now}");
            
            $this->newLine();
        }

        return 0;
    }
}
