<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Jobs\SyncAuctionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:sync {--daemon : Run as a continuous daemon loop} {--interval=10 : Sleep interval in seconds for daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active auctions with external storefronts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daemon = $this->option('daemon');
        $interval = (int) $this->option('interval');

        if ($daemon) {
            $this->info("Starting Auction Sync Daemon (Interval: {$interval}s)...");
            Log::info("Auction Sync Daemon started.");

            while (true) {
                $this->syncActiveAuctions();
                
                // Allow graceful interruption
                if (connection_aborted()) {
                    break;
                }

                sleep($interval);
            }
        } else {
            $this->info("Running one-time Auction Sync...");
            $this->syncActiveAuctions();
        }

        return Command::SUCCESS;
    }

    /**
     * Find active auctions and dispatch sync jobs.
     */
    private function syncActiveAuctions(): void
    {
        $auctions = Auction::where('is_active', true)
            ->where('status', 'active')
            ->get();

        $this->info("Found " . $auctions->count() . " active auctions to sync.");

        foreach ($auctions as $auction) {
            $this->line("Dispatching SyncAuctionJob for ID: {$auction->id} ({$auction->platform})");
            SyncAuctionJob::dispatch($auction->id);
        }
    }
}
