<?php

namespace App\Jobs;

use App\Models\AuctionLot;
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

class IvaluaProxyBidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $auctionLotId;

    public function __construct(int $auctionLotId)
    {
        $this->auctionLotId = $auctionLotId;
    }

    public function handle(AutomationService $automation): void
    {
        $lot = AuctionLot::with('auction')->find($this->auctionLotId);
        $auction = $lot?->auction;

        if (!$lot || !$auction || !$lot->is_active || $lot->status !== 'active') {
            return;
        }

        $proxyBid = ProxyBid::where('auction_lot_id', $this->auctionLotId)
            ->where('status', 'active')
            ->first();

        if (!$proxyBid) {
            return;
        }

        $lockKey = "proxy_lock_{$lot->id}";
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            return;
        }

        try {
            $syncData = $automation->runCommand('sync', 'ivalua', [
                'url' => $auction->external_url,
                'lot_id' => $lot->external_lot_id,
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
            ]);

            $oldBid = $lot->current_bid;
            $currentBid = $syncData['current_bid'] ?? $lot->current_bid;
            $increment = $syncData['bid_increment'] ?? $lot->bid_increment;
            $timeRemaining = $syncData['time_remaining'] ?? $lot->time_remaining;
            $status = $syncData['status'] ?? $lot->status;

            $lot->update([
                'current_bid' => $currentBid,
                'bid_increment' => $increment,
                'time_remaining' => $timeRemaining,
                'status' => $status,
                'last_synced_at' => now(),
                'last_sync_error' => null,
            ]);

            if ($currentBid > $oldBid) {
                $exists = BidHistory::where('auction_lot_id', $lot->id)->where('amount', $currentBid)->exists();
                if (!$exists) {
                    BidHistory::create([
                        'auction_id' => $auction->id,
                        'auction_lot_id' => $lot->id,
                        'amount' => $currentBid,
                        'source' => 'external',
                        'status' => 'successful',
                    ]);
                }
            }

            if ($status === 'ended' || $status === 'failed') {
                $proxyBid->update([
                    'status' => 'completed',
                    'stopped_at' => now(),
                    'stop_reason' => 'Auction ended.',
                ]);
                return;
            }

            $lastSuccessBid = BidHistory::where('auction_lot_id', $lot->id)
                ->where('source', 'user')
                ->where('status', 'successful')
                ->orderBy('amount', 'desc')
                ->first();

            if ($lastSuccessBid && $currentBid <= $lastSuccessBid->amount) {
                return;
            }

            $nextBid = $currentBid + $increment;

            if ($nextBid > $proxyBid->max_amount) {
                $proxyBid->update([
                    'status' => 'stopped',
                    'stopped_at' => now(),
                    'stop_reason' => 'Max bid amount reached.',
                ]);

                Bid::create([
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_type' => 'proxy',
                    'amount' => $proxyBid->max_amount,
                    'status' => 'max_reached',
                    'failure_reason' => "Required bid {$nextBid} exceeds max amount.",
                    'processed_at' => now(),
                ]);
                return;
            }

            $bid = Bid::create([
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
                'user_id' => $proxyBid->user_id,
                'bid_type' => 'proxy',
                'amount' => $nextBid,
                'max_amount' => $proxyBid->max_amount,
                'status' => 'processing',
            ]);

            try {
                $result = $automation->runCommand('place-bid', 'ivalua', [
                    'url' => $auction->external_url,
                    'amount' => $nextBid,
                    'lot_id' => $lot->external_lot_id,
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                    'user_id' => $proxyBid->user_id,
                ]);

                $bid->update([
                    'status' => 'successful',
                    'external_response' => $result,
                    'processed_at' => now(),
                ]);

                $proxyBid->update([
                    'current_auto_bid' => $nextBid,
                ]);

                $lot->update([
                    'current_bid' => $nextBid,
                ]);

                BidHistory::create([
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_id' => $bid->id,
                    'amount' => $nextBid,
                    'source' => 'user',
                    'status' => 'successful',
                ]);

            } catch (\Exception $bidEx) {
                $bid->update([
                    'status' => 'failed',
                    'failure_reason' => $bidEx->getMessage(),
                    'processed_at' => now(),
                ]);

                BidHistory::create([
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                    'user_id' => $proxyBid->user_id,
                    'bid_id' => $bid->id,
                    'amount' => $nextBid,
                    'source' => 'user',
                    'status' => 'failed',
                ]);
            }

        } catch (\Exception $e) {
            // silent — proxy state is in DB
        } finally {
            $lock->release();
        }
    }
}
