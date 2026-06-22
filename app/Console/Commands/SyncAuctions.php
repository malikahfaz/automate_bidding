<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Jobs\BulkSyncIvaluaJob;
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
        $ivaluaCount = Auction::where('platform', 'ivalua')
            ->where('is_active', true)
            ->where('status', 'active')
            ->count();

        $bstockAuctions = Auction::where('platform', 'bstock')
            ->where('is_active', true)
            ->where('status', 'active')
            ->get();

        if ($ivaluaCount > 0) {
            $this->info("Dispatching 1 BulkSyncIvaluaJob for {$ivaluaCount} Ivalua lot(s) (single browser session).");
            BulkSyncIvaluaJob::dispatch();
        }

        if ($bstockAuctions->isNotEmpty()) {
            $this->info('Dispatching ' . $bstockAuctions->count() . ' B-Stock sync job(s).');
            foreach ($bstockAuctions as $auction) {
                SyncAuctionJob::dispatch($auction->id);
            }
        }

        if ($ivaluaCount === 0 && $bstockAuctions->isEmpty()) {
            $this->info('No active auctions to sync.');
        }
    }
}
