<?php

namespace App\Jobs;

use App\Models\PlatformAccount;
use App\Services\AutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshPlatformSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automation): void
    {
        $accounts = PlatformAccount::where('status', '!=', 'disabled')->get();

        foreach ($accounts as $account) {
            Log::info("Refreshing platform session for: {$account->platform} ({$account->email})");
            try {
                $automation->runCommand('login', $account->platform);
                Log::info("Successfully refreshed session for: {$account->platform}");
            } catch (\Exception $e) {
                Log::error("Failed to refresh session for {$account->platform}: " . $e->getMessage());
            }
        }
    }
}
