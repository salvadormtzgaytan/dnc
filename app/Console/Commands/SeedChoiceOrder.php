<?php

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Console\Command;

class SeedChoiceOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-choice-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    ExamAttempt::whereNull('choice_order')->orWhere('choice_order','[]')->chunk(100, function($attempts) {
        foreach ($attempts as $attempt) {
            $choiceOrder = [];
            Question::with('choices')
                ->whereIn('id', $attempt->question_order)
                ->get()
                ->each(function($q) use (&$choiceOrder) {
                    $ids = $q->choices->pluck('id')->toArray();
                    if ($q->shuffle_choices) shuffle($ids);
                    $choiceOrder['question_'.$q->id] = $ids;
                });
            $attempt->choice_order = $choiceOrder;
            $attempt->save();
        }
    });
    $this->info('choice_order poblado en todos los intentos existentes.');
}

}
