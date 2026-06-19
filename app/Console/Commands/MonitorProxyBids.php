<?php

namespace App\Console\Commands;

use App\Models\ProxyBid;
use App\Jobs\IvaluaProxyBidJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorProxyBids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy-bids:monitor {--daemon : Run as a continuous daemon loop} {--interval=5 : Sleep interval in seconds for daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor active custom proxy bids and place incremental bids when outbid';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daemon = $this->option('daemon');
        $interval = (int) $this->option('interval');

        if ($daemon) {
            $this->info("Starting Proxy Bids Monitor Daemon (Interval: {$interval}s)...");
            Log::info("Proxy Bids Monitor Daemon started.");

            while (true) {
                $this->checkProxyBids();

                if (connection_aborted()) {
                    break;
                }

                sleep($interval);
            }
        } else {
            $this->info("Running one-time Proxy Bids Monitor check...");
            $this->checkProxyBids();
        }

        return Command::SUCCESS;
    }

    /**
     * Find active proxy bids and dispatch processing jobs.
     */
    private function checkProxyBids(): void
    {
        $proxies = ProxyBid::where('status', 'active')->get();

        if ($proxies->isNotEmpty()) {
            $this->info("Found " . $proxies->count() . " active proxy bids to check.");
        }

        foreach ($proxies as $proxy) {
            $this->line("Dispatching IvaluaProxyBidJob for Auction ID: {$proxy->auction_id}");
            IvaluaProxyBidJob::dispatch($proxy->auction_id);
        }
    }
}
