<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\AuctionLot;
use App\Models\BidHistory;
use App\Services\AutomationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportIvaluaAuctions extends Command
{
    protected $signature = 'ivalua:import-auctions
                            {--limit=0 : Max console pages to scan (0 = all browse pages)}
                            {--purge-mock : Remove old mock Ivalua/B-Stock demo auctions}
                            {--daemon : Re-import on interval (used by automation:stack)}
                            {--interval=3600 : Seconds between imports when --daemon}';

    protected $description = 'Import browse events and console lots from T-Mobile Ivalua';

    public function handle(AutomationService $automation): int
    {
        if ($this->option('daemon')) {
            $interval = max(300, (int) $this->option('interval'));
            $this->info("Ivalua import daemon started (every {$interval}s). Ctrl+C to stop.");

            while (true) {
                $this->runImport($automation);

                if (connection_aborted()) {
                    break;
                }

                sleep($interval);
            }

            return Command::SUCCESS;
        }

        return $this->runImport($automation);
    }

    private function runImport(AutomationService $automation): int
    {
        $this->info('Connecting to T-Mobile Ivalua and importing events + lots...');

        try {
            $result = $automation->runCommand('import-catalog', 'ivalua', [
                'limit_consoles' => (int) $this->option('limit'),
            ]);
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $events = $result['events'] ?? [];
        $lots = $result['lots'] ?? [];

        if (empty($events) && empty($lots)) {
            $this->warn('No data returned from Ivalua. Check master credentials and login.');
            return Command::FAILURE;
        }

        if ($this->option('purge-mock')) {
            $removedEvents = Auction::where(function ($q) {
                $q->where('external_url', 'like', '%auction_browse_extranet%')
                    ->orWhere('external_url', 'like', '%ellectmobility.com%');
            })->delete();
            $this->info("Removed {$removedEvents} mock/outdated event records.");
        }

        $eventsImported = 0;
        $eventsUpdated = 0;
        $lotsImported = 0;
        $lotsUpdated = 0;
        $lotsSkipped = 0;

        foreach ($events as $event) {
            $auction = Auction::updateOrCreate(
                [
                    'platform' => 'ivalua',
                    'external_event_id' => $event['external_event_id'],
                ],
                [
                    'auction_group' => $event['auction_group'] ?? null,
                    'external_url' => $event['external_url'],
                    'browse_url' => $event['browse_url'] ?? 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet',
                    'title' => Str::limit($event['title'] ?? ('Event ' . $event['external_event_id']), 250, ''),
                    'lots_count' => $event['lots_count'] ?? 0,
                    'starts_at' => !empty($event['starts_at']) ? Carbon::parse($event['starts_at']) : null,
                    'ends_at' => !empty($event['ends_at']) ? Carbon::parse($event['ends_at']) : null,
                    'status' => ($event['status'] ?? 'active') === 'active' ? 'active' : 'paused',
                    'is_active' => true,
                    'is_featured' => $eventsImported < 4,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]
            );

            if ($auction->wasRecentlyCreated) {
                $eventsImported++;
            } else {
                $eventsUpdated++;
            }
        }

        $eventByUrl = Auction::where('platform', 'ivalua')
            ->whereNotNull('external_event_id')
            ->get()
            ->keyBy('external_url');

        // Deduplicate scraped lots (same console + lot ID can appear twice in HTML)
        $uniqueLots = [];
        foreach ($lots as $lot) {
            $url = $lot['external_url'] ?? '';
            $lotId = $lot['external_lot_id'] ?? '';
            if (!$url || !$lotId) {
                continue;
            }
            $uniqueLots[$url . '|' . $lotId] = $lot;
        }

        foreach ($uniqueLots as $lot) {
            $event = $eventByUrl->get($lot['external_url'] ?? '');

            if (!$event) {
                $lotsSkipped++;
                continue;
            }

            $endsAt = !empty($lot['ends_at']) ? Carbon::parse($lot['ends_at']) : null;

            $auctionLot = AuctionLot::updateOrCreate(
                [
                    'auction_id' => $event->id,
                    'external_lot_id' => $lot['external_lot_id'],
                ],
                [
                    'title' => Str::limit($lot['title'] ?? $lot['external_lot_id'], 250, ''),
                    'current_bid' => $lot['current_bid'] ?? 0,
                    'bid_increment' => $lot['bid_increment'] ?? 1,
                    'time_remaining' => $lot['time_remaining'] ?? '',
                    'ends_at' => $endsAt,
                    'status' => ($lot['status'] ?? 'active') === 'active' ? 'active' : 'paused',
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]
            );

            if ($auctionLot->wasRecentlyCreated) {
                $lotsImported++;
            } else {
                $lotsUpdated++;
            }

            if ($auctionLot->current_bid > 0) {
                BidHistory::firstOrCreate(
                    [
                        'auction_id' => $event->id,
                        'auction_lot_id' => $auctionLot->id,
                        'amount' => $auctionLot->current_bid,
                        'source' => 'external',
                    ],
                    ['status' => 'successful', 'created_at' => now()]
                );
            }
        }

        foreach (Auction::where('platform', 'ivalua')->get() as $event) {
            $count = $event->lots()->count();
            if ($count > 0) {
                $event->update(['lots_count' => $count]);
            }

            if (!$event->auction_group) {
                $firstLot = $event->lots()->orderBy('id')->first();
                if ($firstLot && preg_match('/^([A-Z]{4})\d/', $firstLot->external_lot_id, $m)) {
                    $event->update(['auction_group' => $m[1]]);
                }
            }
        }

        $totalEvents = Auction::where('platform', 'ivalua')->count();
        $totalLots = AuctionLot::whereHas('auction', fn ($q) => $q->where('platform', 'ivalua'))->count();
        $consolesFound = (int) ($result['consoles_found'] ?? 0);
        $browseTotal = (int) ($result['browse_total'] ?? $consolesFound);
        $nullGroups = Auction::where('platform', 'ivalua')->whereNull('auction_group')->count();

        $this->newLine();
        $this->info('Import successful — no errors.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records reported by Ivalua browse grid', $browseTotal],
                ['Events saved in database', "{$totalEvents} ({$eventsImported} new, {$eventsUpdated} updated this run)"],
                ['Lots saved in database', "{$totalLots} ({$lotsImported} new, {$lotsUpdated} updated this run)"],
                ['Events missing auction_group', $nullGroups],
            ]
        );

        if ($nullGroups > 0) {
            $this->warn("{$nullGroups} event(s) still have NULL auction_group — re-run import after fix.");
        }

        if ($lotsSkipped > 0) {
            $this->warn("Skipped {$lotsSkipped} lot(s) with no matching event.");
        }

        if ($browseTotal > 0 && $totalEvents < $browseTotal) {
            $this->warn("Only {$totalEvents}/{$browseTotal} browse records saved. Re-run: php artisan ivalua:import-auctions --limit=0");
        }

        $this->newLine();
        $this->line('Website: open /auctions to browse imported lots.');

        Log::info("Ivalua import OK: {$totalEvents} events, {$totalLots} lots in DB.");

        return Command::SUCCESS;
    }
}
