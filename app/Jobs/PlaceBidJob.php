<?php

namespace App\Jobs;

use App\Models\Bid;
use App\Models\BidHistory;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlaceBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Bids should not auto-retry standard queue retries without checks

    protected $bidId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $bidId)
    {
        $this->bidId = $bidId;
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService): void
    {
        $bid = Bid::find($this->bidId);
        if (!$bid || $bid->status !== 'pending') {
            return;
        }

        $auction = $bid->auction;
        if (!$auction || !$auction->is_active) {
            $bid->update([
                'status' => 'failed',
                'failure_reason' => 'Auction is no longer active or exists.',
                'processed_at' => now()
            ]);
            return;
        }

        Log::info("Attempting to place bid ID: {$bid->id} of amount {$bid->amount} on auction {$auction->id}");

        // Acquire lock for this auction
        $lockKey = "auction_lock_{$auction->id}";
        $lock = Cache::lock($lockKey, 60); // 60s lock max

        if (!$lock->get()) {
            Log::warning("Could not acquire lock for auction {$auction->id}. Requeuing bid job {$bid->id}");
            $this->release(5); // retry in 5s
            return;
        }

        $bid->update(['status' => 'processing']);

        try {
            // Place the bid via browser automation
            $result = $automationService->runCommand('place-bid', $auction->platform, [
                'url' => $auction->external_url,
                'amount' => $bid->amount,
                'auction_id' => $auction->id,
                'user_id' => $bid->user_id
            ]);

            // If successful, update the bid and auction state
            $bid->update([
                'status' => 'successful',
                'external_response' => $result,
                'processed_at' => now()
            ]);

            // Force update current bid in the local database
            $auction->update([
                'current_bid' => $bid->amount,
                'last_synced_at' => now()
            ]);

            // Add to bid histories
            BidHistory::create([
                'auction_id' => $auction->id,
                'user_id' => $bid->user_id,
                'bid_id' => $bid->id,
                'amount' => $bid->amount,
                'source' => 'user',
                'status' => 'successful'
            ]);

            Log::info("Successfully placed bid ID {$bid->id}");

        } catch (\Exception $e) {
            Log::error("Failed to place bid ID {$bid->id}: " . $e->getMessage());

            $bid->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'processed_at' => now()
            ]);

            // Add failed history entry
            BidHistory::create([
                'auction_id' => $auction->id,
                'user_id' => $bid->user_id,
                'bid_id' => $bid->id,
                'amount' => $bid->amount,
                'source' => 'user',
                'status' => 'failed'
            ]);

        } finally {
            $lock->release();
        }
    }
}
