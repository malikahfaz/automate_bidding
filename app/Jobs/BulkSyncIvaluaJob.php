<?php

namespace App\Jobs;

use App\Models\AuctionLot;
use App\Models\BidHistory;
use App\Services\AutomationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BulkSyncIvaluaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 300;

    public function handle(AutomationService $automation): void
    {
        $lock = Cache::lock('bulk_sync_ivalua', 240);

        if (!$lock->get()) {
            return;
        }

        try {
            $lots = AuctionLot::with('auction')
                ->where('is_active', true)
                ->where('status', 'active')
                ->whereHas('auction', function ($q) {
                    $q->where('platform', 'ivalua')->where('is_active', true);
                })
                ->get();

            if ($lots->isEmpty()) {
                return;
            }

            $consoles = $lots->groupBy(fn ($lot) => $lot->auction->external_url)->map(function ($items, $url) {
                return [
                    'url' => $url,
                    'lot_ids' => $items->pluck('external_lot_id')->filter()->values()->all(),
                ];
            })->values()->all();

            $result = $automation->runCommand('bulk-sync', 'ivalua', [
                'consoles' => $consoles,
            ]);

            $lotIndex = $lots->keyBy(
                fn ($lot) => $lot->auction->external_url . '|' . $lot->external_lot_id
            );

            foreach ($result['lots'] ?? [] as $lotData) {
                $lotId = $lotData['external_lot_id'] ?? $lotData['lot_id'] ?? null;
                $url = $lotData['external_url'] ?? null;

                if (!$lotId || !$url) {
                    continue;
                }

                $dbLot = $lotIndex->get($url . '|' . $lotId);

                if (!$dbLot) {
                    continue;
                }

                $oldBid = $dbLot->current_bid;
                $endsAt = !empty($lotData['ends_at'])
                    ? Carbon::parse($lotData['ends_at'])
                    : $dbLot->ends_at;

                $dbLot->update([
                    'title' => $lotData['title'] ?? $dbLot->title,
                    'description' => $lotData['description'] ?? $dbLot->description,
                    'quantity' => $lotData['quantity'] ?? $dbLot->quantity,
                    'cosmetic_grade' => $lotData['cosmetic_grade'] ?? $dbLot->cosmetic_grade,
                    'current_bid' => $lotData['current_bid'] ?? $dbLot->current_bid,
                    'bid_increment' => $lotData['bid_increment'] ?? $dbLot->bid_increment,
                    'time_remaining' => $lotData['time_remaining'] ?? $dbLot->time_remaining,
                    'ends_at' => $endsAt,
                    'status' => $lotData['status'] ?? $dbLot->status,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]);

                if ($dbLot->current_bid > $oldBid) {
                    $exists = BidHistory::where('auction_lot_id', $dbLot->id)
                        ->where('amount', $dbLot->current_bid)
                        ->exists();

                    if (!$exists) {
                        BidHistory::create([
                            'auction_id' => $dbLot->auction_id,
                            'auction_lot_id' => $dbLot->id,
                            'amount' => $dbLot->current_bid,
                            'source' => 'external',
                            'status' => 'successful',
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            if (config('automation.verbose_log')) {
                Log::error('BulkSyncIvaluaJob failed: ' . $e->getMessage());
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
