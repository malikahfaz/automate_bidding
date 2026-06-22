<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunAutomationStack extends Command
{
    protected $signature = 'automation:stack
                            {--sync-interval=10 : Live bid sync interval in seconds}
                            {--proxy-interval=5 : Proxy bid check interval in seconds}
                            {--watch-interval=600 : Check Ivalua browse for NEW auctions every N seconds (0 = disable)}
                            {--import-interval=0 : Full re-import every N seconds even if no new events (0 = off)}
                            {--skip-import : Skip initial import (use existing DB only)}
                            {--skip-credentials : Skip syncing .env credentials to database}';

    protected $description = 'One command: credentials, import, watch for new auctions, sync bids, proxy bidding';

    public function handle(): int
    {
        $syncInterval = (int) $this->option('sync-interval');
        $proxyInterval = (int) $this->option('proxy-interval');
        $watchInterval = (int) $this->option('watch-interval');
        $importInterval = (int) $this->option('import-interval');
        $artisan = PHP_BINARY . ' ' . base_path('artisan');

        $this->newLine();
        $this->info('=== Automation Stack — single command ===');
        $this->newLine();

        if (!$this->option('skip-credentials')) {
            $this->info('[1/3] Syncing master credentials from .env ...');
            $this->call('platform:sync-credentials');
            $this->newLine();
        }

        if (!$this->option('skip-import')) {
            $this->info('[2/3] Importing all Ivalua browse events + console lots ...');
            $this->line('      (first run may take 1–2 minutes)');
            $importExit = $this->call('ivalua:import-auctions', ['--limit' => 0]);
            if ($importExit !== Command::SUCCESS) {
                $this->warn('Import reported an issue — continuing with existing DB data.');
            }
            $this->newLine();
        } else {
            $this->line('[2/3] Import skipped (--skip-import).');
            $this->newLine();
        }

        $this->info('[3/3] Starting background workers (Ctrl+C to stop) ...');
        $this->line('  • Queue worker — places bids on Ivalua');
        $this->line("  • Bid sync every {$syncInterval}s — live prices on website");
        $this->line("  • Proxy monitor every {$proxyInterval}s — auto-bidding");
        if (!$this->option('skip-import') && $watchInterval > 0) {
            $mins = round($watchInterval / 60, 1);
            $this->line("  • New auction watch every {$watchInterval}s (~{$mins} min) — auto-import if Ivalua adds events");
        }
        if (!$this->option('skip-import') && $importInterval > 0) {
            $this->line("  • Scheduled full re-import every {$importInterval}s");
        }
        $this->newLine();

        $processes = [
            escapeshellarg("{$artisan} queue:work --sleep=3 --tries=3"),
            escapeshellarg("{$artisan} auctions:sync --daemon --interval={$syncInterval}"),
            escapeshellarg("{$artisan} proxy-bids:monitor --daemon --interval={$proxyInterval}"),
        ];

        $names = 'worker,sync,proxy';
        $colors = 'blue,green,magenta';

        if (!$this->option('skip-import') && $watchInterval > 0) {
            $processes[] = escapeshellarg("{$artisan} ivalua:watch-auctions --daemon --interval={$watchInterval}");
            $names .= ',watch';
            $colors .= ',yellow';
        }

        if (!$this->option('skip-import') && $importInterval > 0) {
            $processes[] = escapeshellarg("{$artisan} ivalua:import-auctions --daemon --interval={$importInterval} --limit=0");
            $names .= ',import';
            $colors .= ',cyan';
        }

        $process = Process::fromShellCommandline(
            implode(' ', [
                'npx concurrently',
                '-n ' . $names,
                '-c ' . $colors,
                ...$processes,
            ]),
            base_path(),
            null,
            null,
            null
        );

        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        return Command::SUCCESS;
    }
}
