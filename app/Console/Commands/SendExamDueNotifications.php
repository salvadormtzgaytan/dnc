<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dnc;
use App\Models\ExamAttempt;
use App\Notifications\ExamDueNotification;
use Carbon\Carbon;

class SendExamDueNotifications extends Command
{
    protected $signature   = 'app:send-exam-due-notifications';
    protected $description = 'Envía un único correo a cada usuario con todos sus exámenes próximos a vencer y nombre de la DNC';

    public function handle(): void
    {
        $today = Carbon::now()->startOfDay();

        Dnc::with(['thresholds', 'assignedUsers.examAttempts.exam'])
            ->chunk(10, function ($dncs) use ($today) {
                foreach ($dncs as $dnc) {
                    // Saltar DNC caducadas
                    if ($today->greaterThan($dnc->end_date->endOfDay())) {
                        continue;
                    }

                    // Rango máximo
                    $maxRange = $dnc->getMaxNotificationRange();
                    if ($maxRange === 0) {
                        continue;
                    }

                    $bccList = collect(explode(',', $dnc->bcc_emails ?? ''))
                        ->map(fn($e) => trim($e))
                        ->filter()
                        ->unique()
                        ->toArray();

                    foreach ($dnc->assignedUsers as $user) {
                        // Recoger todos los intentos pendientes de este usuario
                        $pending = $user->examAttempts->whereNull('finished_at');

                        // Si no hay intentos, simular con los exámenes de la DNC
                        if ($pending->isEmpty()) {
                            $pending = $dnc->exams->map(fn($exam) => (object)[
                                'exam' => $exam,
                                'id'   => 0,
                            ]);
                        }

                        // Filtrar por todos los thresholds dentro de rango
                        $dueAll = collect();
                        foreach ($dnc->thresholds as $threshold) {
                            if ($threshold->days_before > $maxRange) {
                                continue;
                            }
                            $targetDate = $today->copy()->addDays($threshold->days_before)->toDateString();
                            $dueAll = $dueAll->merge(
                                $pending->filter(fn($a) =>
                                    Carbon::parse($a->exam->end_at)->toDateString() === $targetDate
                                )
                            );
                        }
                        $dueAll = $dueAll->unique('exam_id');

                        if ($dueAll->isNotEmpty()) {
                            $this->info("Enviando resumen a {$user->email} (DNC: {$dnc->name})");
                            $user->notify(new ExamDueNotification($dueAll, $bccList, $dnc->name));
                        }
                    }
                }
            });

        $this->info('✅ Resúmenes de notificaciones enviados correctamente.');
    }
}
