<?php

namespace App\Jobs;

use App\Models\Auction;
use App\Models\BidHistory;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAuctionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    protected $auctionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $auctionId)
    {
        $this->auctionId = $auctionId;
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService): void
    {
        $auction = Auction::find($this->auctionId);
        if (!$auction || !$auction->is_active) {
            return;
        }

        Log::info("Syncing auction ID: {$auction->id} from {$auction->platform}");

        try {
            $data = $automationService->runCommand('sync', $auction->platform, [
                'url' => $auction->external_url,
                'auction_id' => $auction->id
            ]);

            // Update Auction State
            $oldBid = $auction->current_bid;
            
            $auction->update([
                'title' => $data['title'] ?? $auction->title,
                'current_bid' => $data['current_bid'] ?? $auction->current_bid,
                'bid_increment' => $data['bid_increment'] ?? $auction->bid_increment,
                'time_remaining' => $data['time_remaining'] ?? $auction->time_remaining,
                'ends_at' => isset($data['ends_at']) ? \Carbon\Carbon::parse($data['ends_at']) : $auction->ends_at,
                'status' => $data['status'] ?? $auction->status,
                'last_synced_at' => now(),
                'last_sync_error' => null
            ]);

            // Record in Bid History if bid increased
            $newBid = $auction->current_bid;
            if ($newBid > $oldBid) {
                // Check if history already has this amount for this auction to avoid duplicates
                $exists = BidHistory::where('auction_id', $auction->id)
                    ->where('amount', $newBid)
                    ->exists();

                if (!$exists) {
                    BidHistory::create([
                        'auction_id' => $auction->id,
                        'amount' => $newBid,
                        'source' => 'external',
                        'status' => 'successful'
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to sync auction ID {$this->auctionId}: " . $e->getMessage());
            
            // On final attempt failure, log details on auction record
            if ($this->attempts() >= $this->tries) {
                $auction->update([
                    'last_sync_error' => $e->getMessage(),
                    'last_synced_at' => now(),
                    // We don't mark active = false immediately to allow retries later, 
                    // but we can set status to failed if needed.
                ]);
            }

            throw $e;
        }
    }
}
