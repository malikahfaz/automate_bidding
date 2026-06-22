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

    public $tries = 1;

    public $timeout = 240;

    protected $bidId;

    public function __construct(int $bidId)
    {
        $this->bidId = $bidId;
    }

    public function handle(AutomationService $automationService): void
    {
        $bid = Bid::with(['lot.auction'])->find($this->bidId);
        if (!$bid || $bid->status !== 'pending') {
            return;
        }

        $lot = $bid->lot;
        $auction = $lot?->auction;

        if (!$lot || !$auction || !$lot->is_active || $lot->status !== 'active') {
            $bid->update([
                'status' => 'failed',
                'failure_reason' => 'Lot is no longer active or exists.',
                'processed_at' => now(),
            ]);
            return;
        }

        if ($auction->platform !== 'ivalua') {
            $bid->update([
                'status' => 'failed',
                'failure_reason' => 'Automated cross-platform bidding is only configured for Ivalua lots.',
                'processed_at' => now(),
            ]);
            return;
        }

        Log::info("PlaceBidJob: bid #{$bid->id} amount {$bid->amount} on lot {$lot->external_lot_id} → Ivalua");

        $lockKey = "lot_lock_{$lot->id}";
        $lock = Cache::lock($lockKey, 120);

        if (!$lock->get()) {
            Log::warning("Could not acquire lock for lot {$lot->id}. Requeuing bid job {$bid->id}");
            $this->release(5);
            return;
        }

        $bid->update(['status' => 'processing']);

        try {
            // 1. Sync live state from Ivalua before bidding
            $syncData = $automationService->runCommand('sync', $auction->platform, [
                'url' => $auction->external_url,
                'lot_id' => $lot->external_lot_id,
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
            ]);

            $currentBid = (float) ($syncData['current_bid'] ?? $lot->current_bid);
            $increment = (float) ($syncData['bid_increment'] ?? $lot->bid_increment);
            $minRequired = $currentBid + $increment;

            $lot->update([
                'current_bid' => $currentBid,
                'bid_increment' => $increment,
                'time_remaining' => $syncData['time_remaining'] ?? $lot->time_remaining,
                'ends_at' => !empty($syncData['ends_at']) ? \Carbon\Carbon::parse($syncData['ends_at']) : $lot->ends_at,
                'last_synced_at' => now(),
            ]);

            if ($bid->amount < $minRequired) {
                $bid->update([
                    'status' => 'failed',
                    'failure_reason' => 'Outbid on Ivalua before execution. Minimum now: $' . number_format($minRequired, 2),
                    'processed_at' => now(),
                ]);

                BidHistory::create([
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                    'user_id' => $bid->user_id,
                    'bid_id' => $bid->id,
                    'amount' => $bid->amount,
                    'source' => 'user',
                    'status' => 'failed',
                ]);

                return;
            }

            // 2. Place bid on Ivalua via master account (Playwright)
            $result = $automationService->runCommand('place-bid', $auction->platform, [
                'url' => $auction->external_url,
                'amount' => $bid->amount,
                'lot_id' => $lot->external_lot_id,
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
                'user_id' => $bid->user_id,
            ]);

            $confirmedBid = (float) ($result['current_bid'] ?? $bid->amount);

            $bid->update([
                'status' => 'successful',
                'external_response' => $result,
                'processed_at' => now(),
            ]);

            $lot->update([
                'current_bid' => max($confirmedBid, (float) $bid->amount),
                'last_synced_at' => now(),
                'last_sync_error' => null,
            ]);

            BidHistory::create([
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
                'user_id' => $bid->user_id,
                'bid_id' => $bid->id,
                'amount' => $bid->amount,
                'source' => 'user',
                'status' => 'successful',
            ]);

            Log::info("PlaceBidJob: bid #{$bid->id} placed on Ivalua successfully.");

        } catch (\Exception $e) {
            Log::error("PlaceBidJob failed bid #{$bid->id}: " . $e->getMessage());

            $bid->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            BidHistory::create([
                'auction_id' => $auction->id,
                'auction_lot_id' => $lot->id,
                'user_id' => $bid->user_id,
                'bid_id' => $bid->id,
                'amount' => $bid->amount,
                'source' => 'user',
                'status' => 'failed',
            ]);

        } finally {
            $lock->release();
        }
    }
}
