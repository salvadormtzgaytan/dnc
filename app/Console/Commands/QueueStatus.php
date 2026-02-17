<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class QueueStatus extends Command
{
    protected $signature = 'queue:status';
    protected $description = 'Muestra el estado de las colas de Redis';

    public function handle()
    {
        $redis = Redis::connection();
        
        $this->info('📊 Estado de las Colas de Redis');
        $this->newLine();
        
        // Cola principal
        $defaultQueue = $redis->llen('queues:default');
        $this->line("Cola 'default': {$defaultQueue} jobs pendientes");
        
        // Jobs fallidos
        $failedJobs = $redis->llen('queues:default:failed');
        $this->line("Jobs fallidos: {$failedJobs}");
        
        // Jobs reservados
        $reservedJobs = $redis->llen('queues:default:reserved');
        $this->line("Jobs en proceso: {$reservedJobs}");
        
        $this->newLine();
        
        // Mostrar todas las claves de colas
        $queueKeys = $redis->keys('queues:*');
        if (!empty($queueKeys)) {
            $this->info('Todas las colas:');
            foreach ($queueKeys as $key) {
                $count = $redis->llen($key);
                $this->line("  - {$key}: {$count}");
            }
        }
        
        return 0;
    }
}
