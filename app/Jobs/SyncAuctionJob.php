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

    public $tries = 3;

    public $backoff = 10;

    protected $auctionId;

    public function __construct(int $auctionId)
    {
        $this->auctionId = $auctionId;
    }

    public function handle(AutomationService $automationService): void
    {
        $auction = Auction::with('lots')->find($this->auctionId);
        if (!$auction || !$auction->is_active) {
            return;
        }

        Log::info("Syncing auction event ID: {$auction->id} from {$auction->platform}");

        try {
            foreach ($auction->lots as $lot) {
                if (!$lot->is_active) {
                    continue;
                }

                $data = $automationService->runCommand('sync', $auction->platform, [
                    'url' => $auction->external_url,
                    'lot_id' => $lot->external_lot_id,
                    'auction_id' => $auction->id,
                    'auction_lot_id' => $lot->id,
                ]);

                $oldBid = $lot->current_bid;

                $lot->update([
                    'title' => $data['title'] ?? $lot->title,
                    'current_bid' => $data['current_bid'] ?? $lot->current_bid,
                    'bid_increment' => $data['bid_increment'] ?? $lot->bid_increment,
                    'time_remaining' => $data['time_remaining'] ?? $lot->time_remaining,
                    'ends_at' => isset($data['ends_at']) ? \Carbon\Carbon::parse($data['ends_at']) : $lot->ends_at,
                    'status' => $data['status'] ?? $lot->status,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]);

                $newBid = $lot->current_bid;
                if ($newBid > $oldBid) {
                    $exists = BidHistory::where('auction_lot_id', $lot->id)
                        ->where('amount', $newBid)
                        ->exists();

                    if (!$exists) {
                        BidHistory::create([
                            'auction_id' => $auction->id,
                            'auction_lot_id' => $lot->id,
                            'amount' => $newBid,
                            'source' => 'external',
                            'status' => 'successful',
                        ]);
                    }
                }
            }

            $auction->update([
                'last_synced_at' => now(),
                'last_sync_error' => null,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to sync auction ID {$this->auctionId}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $auction->update([
                    'last_sync_error' => $e->getMessage(),
                    'last_synced_at' => now(),
                ]);
            }

            throw $e;
        }
    }
}
