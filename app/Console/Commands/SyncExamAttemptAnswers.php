<?php
namespace App\Console\Commands;

use App\Models\ExamAttempt;
use Illuminate\Console\Command;

class SyncExamAttemptAnswers extends Command
{
    protected $signature = 'exam:sync-answers';
    protected $description = 'Sincroniza respuestas de exam_attempts.answers a exam_attempt_answers';

    public function handle()
    {
        $attempts = ExamAttempt::where('status', 'completed')
            ->whereNotNull('answers')
            ->get();

        $bar = $this->output->createProgressBar($attempts->count());

        $attempts->each(function (ExamAttempt $attempt) use ($bar) {
            $attempt->syncAnswersToTable();
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info("Sincronizados {$attempts->count()} intentos de examen.");
        
        return Command::SUCCESS;
    }
}