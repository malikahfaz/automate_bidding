<?php

namespace App\Jobs;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\BidHistory;
use App\Models\ProxyBid;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IvaluaProxyBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

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
    public function handle(AutomationService $automation): void
    {
        $auction = Auction::find($this->auctionId);
        if (!$auction || !$auction->is_active || $auction->status !== 'active') {
            return;
        }

        // Get the active proxy bid for this auction
        $proxyBid = ProxyBid::where('auction_id', $this->auctionId)
            ->where('status', 'active')
            ->first();

        if (!$proxyBid) {
            return;
        }

        // Lock to avoid race conditions in proxy bidding check
        $lockKey = "proxy_lock_{$auction->id}";
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            return; // Exit, another proxy check is running
        }

        Log::info("Running Ivalua Proxy Bidding check for Auction: {$auction->id}, User: {$proxyBid->user_id}");

        try {
            // 1. Sync latest auction state
            $syncData = $automation->runCommand('sync', 'ivalua', [
                'url' => $auction->external_url,
                'auction_id' => $auction->id
            ]);

            $oldBid = $auction->current_bid;
            $currentBid = $syncData['current_bid'] ?? $auction->current_bid;
            $increment = $syncData['bid_increment'] ?? $auction->bid_increment;
            $timeRemaining = $syncData['time_remaining'] ?? $auction->time_remaining;
            $status = $syncData['status'] ?? $auction->status;

            // Update local state
            $auction->update([
                'current_bid' => $currentBid,
                'bid_increment' => $increment,
                'time_remaining' => $timeRemaining,
                'status' => $status,
                'last_synced_at' => now(),
                'last_sync_error' => null
            ]);

            // Save history if external bid changed
            if ($currentBid > $oldBid) {
                $exists = BidHistory::where('auction_id', $auction->id)->where('amount', $currentBid)->exists();
                if (!$exists) {
                    BidHistory::create([
                        'auction_id' => $auction->id,
                        'amount' => $currentBid,
                        'source' => 'external',
                        'status' => 'successful'
                    ]);
                }
            }

            // Check if auction has ended
            if ($status === 'ended' || $status === 'failed') {
                $proxyBid->update([
                    'status' => 'completed',
                    'stopped_at' => now(),
                    'stop_reason' => 'Auction ended.'
                ]);
                return;
            }

            // 2. Check if we are winning
            // We look at our system's last placed successful bid for this auction.
            // If the latest bid on the platform equals our successful bid, we are winning.
            $lastSuccessBid = BidHistory::where('auction_id', $auction->id)
                ->where('source', 'user')
                ->where('status', 'successful')
                ->orderBy('amount', 'desc')
                ->first();

            if ($lastSuccessBid && $currentBid <= $lastSuccessBid->amount) {
                Log::info("System is currently winning the auction {$auction->id} (Current bid: {$currentBid}, Our bid: {$lastSuccessBid->amount}). No bid needed.");
                return;
            }

            // 3. We are outbid! Calculate next bid
            $nextBid = $currentBid + $increment;

            if ($nextBid > $proxyBid->max_amount) {
                Log::info("Next required bid {$nextBid} exceeds user max amount {$proxyBid->max_amount}. Stopping proxy bid.");
                $proxyBid->update([
                    'status' => 'stopped',
                    'stopped_at' => now(),
                    'stop_reason' => 'Max bid amount reached.'
                ]);

                // Create a proxy bid record indicating limit reached
                Bid::create([
                    'auction_id' => $auction->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_type' => 'proxy',
                    'amount' => $proxyBid->max_amount,
                    'status' => 'max_reached',
                    'failure_reason' => "Required bid {$nextBid} exceeds max amount.",
                    'processed_at' => now()
                ]);
                return;
            }

            // 4. Place next bid
            Log::info("Proxy placing bid of {$nextBid} (Max limit: {$proxyBid->max_amount})");

            $bid = Bid::create([
                'auction_id' => $auction->id,
                'user_id' => $proxyBid->user_id,
                'bid_type' => 'proxy',
                'amount' => $nextBid,
                'max_amount' => $proxyBid->max_amount,
                'status' => 'processing'
            ]);

            try {
                $result = $automation->runCommand('place-bid', 'ivalua', [
                    'url' => $auction->external_url,
                    'amount' => $nextBid,
                    'auction_id' => $auction->id,
                    'user_id' => $proxyBid->user_id
                ]);

                $bid->update([
                    'status' => 'successful',
                    'external_response' => $result,
                    'processed_at' => now()
                ]);

                $proxyBid->update([
                    'current_auto_bid' => $nextBid
                ]);

                $auction->update([
                    'current_bid' => $nextBid
                ]);

                BidHistory::create([
                    'auction_id' => $auction->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_id' => $bid->id,
                    'amount' => $nextBid,
                    'source' => 'user',
                    'status' => 'successful'
                ]);

                Log::info("Proxy bid {$bid->id} of amount {$nextBid} placed successfully.");

            } catch (\Exception $bidEx) {
                Log::error("Proxy bid placement failed for bid ID {$bid->id}: " . $bidEx->getMessage());
                
                $bid->update([
                    'status' => 'failed',
                    'failure_reason' => $bidEx->getMessage(),
                    'processed_at' => now()
                ]);

                BidHistory::create([
                    'auction_id' => $auction->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_id' => $bid->id,
                    'amount' => $nextBid,
                    'source' => 'user',
                    'status' => 'failed'
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error in IvaluaProxyBidJob for auction {$this->auctionId}: " . $e->getMessage());
        } finally {
            $lock->release();
        }
    }
}
