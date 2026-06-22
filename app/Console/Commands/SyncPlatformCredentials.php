<?php

namespace App\Console\Commands;

use App\Models\PlatformAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class SyncPlatformCredentials extends Command
{
    protected $signature = 'platform:sync-credentials';

    protected $description = 'Sync master platform credentials from .env into platform_accounts table';

    public function handle(): int
    {
        $map = [
            'ivalua' => [
                'email' => env('IVALUA_MASTER_EMAIL'),
                'password' => env('IVALUA_MASTER_PASSWORD'),
            ],
            'bstock' => [
                'email' => env('BSTOCK_MASTER_EMAIL'),
                'password' => env('BSTOCK_MASTER_PASSWORD'),
            ],
        ];

        $updated = 0;

        foreach ($map as $platform => $creds) {
            if (empty($creds['email']) || empty($creds['password'])) {
                if ($platform === 'bstock') {
                    $this->line("Note: B-Stock not configured (optional). Set BSTOCK_MASTER_EMAIL and BSTOCK_MASTER_PASSWORD in .env if needed.");
                } else {
                    $this->warn("Skipping {$platform}: set " . strtoupper($platform) . "_MASTER_EMAIL and _PASSWORD in .env");
                }
                continue;
            }

            PlatformAccount::updateOrCreate(
                ['platform' => $platform],
                [
                    'email' => $creds['email'],
                    'encrypted_password' => Crypt::encryptString($creds['password']),
                    'status' => 'active',
                ]
            );

            $this->info("Synced master credentials for {$platform} ({$creds['email']})");
            $updated++;
        }

        if ($updated === 0) {
            $this->error('No credentials synced. Add IVALUA_MASTER_EMAIL/PASSWORD to .env or use Admin → Platform Accounts.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
