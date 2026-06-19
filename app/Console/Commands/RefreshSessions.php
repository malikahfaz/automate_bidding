<?php

namespace App\Console\Commands;

use App\Jobs\RefreshPlatformSessionJob;
use Illuminate\Console\Command;

class RefreshSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:refresh-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh session cookies for all active platform accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Dispatching RefreshPlatformSessionJob...");
        RefreshPlatformSessionJob::dispatch();
        $this->info("Job dispatched successfully.");
        
        return Command::SUCCESS;
    }
}
