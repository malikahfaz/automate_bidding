<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Services\AutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WatchIvaluaAuctions extends Command
{
    protected $signature = 'ivalua:watch-auctions
                            {--daemon : Run continuously in the background}
                            {--interval=600 : Seconds between browse checks (default 10 minutes)}';

    protected $description = 'Watch Ivalua browse page for new auction events and auto-import when found';

    public function handle(AutomationService $automation): int
    {
        if ($this->option('daemon')) {
            $interval = max(120, (int) $this->option('interval'));
            $this->info("Ivalua auction watcher started — checking every {$interval}s for new events.");

            while (true) {
                $this->checkForNewAuctions($automation);

                if (connection_aborted()) {
                    break;
                }

                sleep($interval);
            }

            return Command::SUCCESS;
        }

        $this->checkForNewAuctions($automation);

        return Command::SUCCESS;
    }

    private function checkForNewAuctions(AutomationService $automation): void
    {
        $knownIds = Auction::where('platform', 'ivalua')
            ->whereNotNull('external_event_id')
            ->pluck('external_event_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        try {
            $result = $automation->runCommand('list-browse-events', 'ivalua');
        } catch (\Exception $e) {
            $this->warn('[' . now()->format('H:i:s') . '] Browse watch failed: ' . $e->getMessage());
            Log::warning('Ivalua watch failed: ' . $e->getMessage());
            return;
        }

        $browseEvents = $result['events'] ?? [];
        $browseIds = collect($browseEvents)
            ->pluck('external_event_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $browseTotal = (int) ($result['browse_total'] ?? count($browseIds));
        $knownCount = count($knownIds);
        $newIds = array_values(array_diff($browseIds, $knownIds));

        if (empty($newIds) && $browseTotal <= $knownCount) {
            $this->line('[' . now()->format('H:i:s') . "] No new auctions (Ivalua: {$browseTotal}, DB: {$knownCount}).");
            return;
        }

        $reason = !empty($newIds)
            ? 'new event ID(s): ' . implode(', ', $newIds)
            : "browse total grew ({$browseTotal} vs DB {$knownCount})";

        $this->info('[' . now()->format('H:i:s') . "] New auction activity detected — {$reason}");
        $this->line('Running full import for new events + lots...');

        Log::info("Ivalua watcher triggering import: {$reason}");

        $lock = Cache::lock('ivalua_import', 900);

        if (!$lock->get()) {
            $this->warn('Import already running — skipping this cycle.');
            return;
        }

        try {
            $this->call('ivalua:import-auctions', ['--limit' => 0]);
        } finally {
            $lock->release();
        }
    }
}
