<?php

namespace App\Services;

use App\Models\PlatformAccount;
use App\Models\AutomationLog;
use App\Models\Auction;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AutomationService
{
    /**
     * Run the browser automation CLI command.
     *
     * @param string $action login|sync|place-bid
     * @param string $platform bstock|ivalua
     * @param array $args Additional arguments (url, amount, auction_id, user_id)
     * @return array
     * @throws \Exception
     */
    public function runCommand(string $action, string $platform, array $args = []): array
    {
        // 1. Fetch credentials
        $account = PlatformAccount::where('platform', $platform)->first();
        if (!$account) {
            throw new \Exception("No master account found for platform: {$platform}");
        }

        $email = $account->email;
        $password = $account->getDecryptedPassword();

        // 2. Prepare paths
        $cliPath = base_path('automation/cli.cjs');
        $cookiesPath = storage_path("app/automation/cookies_{$platform}.json");
        
        $screenshotName = "{$platform}_{$action}_" . time() . ".png";
        $screenshotPath = storage_path("app/public/automation/screenshots/{$screenshotName}");
        $screenshotPublicUrl = "storage/automation/screenshots/{$screenshotName}";

        // Ensure directories exist
        if (!file_exists(dirname($cookiesPath))) {
            mkdir(dirname($cookiesPath), 0755, true);
        }
        if (!file_exists(dirname($screenshotPath))) {
            mkdir(dirname($screenshotPath), 0755, true);
        }

        $mockMode = (bool) config('automation.mock_mode', false);

        // 3. Build command line
        $cmd = [
            'node',
            $cliPath,
            '--action', $action,
            '--platform', $platform,
            '--email', $email,
            '--password', $password,
            '--cookies-path', $cookiesPath,
            '--screenshot-path', $screenshotPath
        ];

        if (isset($args['url'])) {
            $cmd[] = '--url';
            $cmd[] = $args['url'];
        }

        if (isset($args['amount'])) {
            $cmd[] = '--amount';
            $cmd[] = (string)$args['amount'];
        }

        if (!empty($args['lot_id'])) {
            $cmd[] = '--lot-id';
            $cmd[] = (string)$args['lot_id'];
        }

        if (array_key_exists('limit_consoles', $args)) {
            $cmd[] = '--limit-consoles';
            $cmd[] = (string) $args['limit_consoles'];
        }

        $payloadPath = null;
        if (!empty($args['consoles'])) {
            $payloadPath = storage_path('app/automation/payload_' . uniqid() . '.json');
            if (!file_exists(dirname($payloadPath))) {
                mkdir(dirname($payloadPath), 0755, true);
            }
            file_put_contents($payloadPath, json_encode(['consoles' => $args['consoles']]));
            $cmd[] = '--payload-path';
            $cmd[] = $payloadPath;
        }

        if ($mockMode) {
            $cmd[] = '--mock';
            $cmd[] = 'true';
        }

        if (isset($args['mock']) && $args['mock']) {
            $cmd[] = '--mock';
            $cmd[] = 'true';
        }

        // 4. Run Process
        $process = new Process($cmd);
        $process->setTimeout(match ($action) {
            'bulk-sync' => 300,
            'import-catalog' => 600,
            'list-browse-events' => 120,
            'place-bid' => 180,
            'sync' => 120,
            default => 90,
        });
        
        $auctionId = $args['auction_id'] ?? null;
        $userId = $args['user_id'] ?? null;

        Log::info("Starting automation process: {$action} for platform {$platform}");
        
        try {
            $process->run();
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            
            // Log raw process execution info for admin debugging
            Log::debug("Automation process output: " . $output);
            if (!empty($errorOutput)) {
                Log::warning("Automation process stderr: " . $errorOutput);
            }

            // 5. Parse output for JSON response
            $json = null;
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) {
                    $json = json_decode($trimmed, true);
                    break;
                }
            }

            if (!$json) {
                // If no JSON was outputted, throw error
                throw new \Exception("Automation script did not return a valid JSON response. Stderr: " . $errorOutput);
            }

            // 6. Handle automation script failure
            if (isset($json['success']) && $json['success'] === false) {
                $errorMsg = $json['error'] ?? 'Unknown automation error';
                $hasScreenshot = isset($json['screenshot']) && file_exists($screenshotPath);
                
                // Save to automation_logs
                AutomationLog::create([
                    'platform' => $platform,
                    'auction_id' => $auctionId,
                    'user_id' => $userId,
                    'action' => $action,
                    'status' => 'failed',
                    'message' => $errorMsg,
                    'payload' => [
                        'url' => $args['url'] ?? null,
                        'amount' => $args['amount'] ?? null,
                        'stderr' => $errorOutput
                    ],
                    'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null
                ]);

                // Update platform account last error
                $account->update([
                    'status' => 'error',
                    'last_error' => $errorMsg
                ]);

                throw new \Exception("Automation Failed: {$errorMsg}");
            }

            // 7. Success log
            $hasScreenshot = file_exists($screenshotPath);
            AutomationLog::create([
                'platform' => $platform,
                'auction_id' => $auctionId,
                'user_id' => $userId,
                'action' => $action,
                'status' => 'success',
                'message' => "Successfully completed action '{$action}'",
                'payload' => $json['data'] ?? null,
                'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null
            ]);

            // Update platform account success
            $account->update([
                'status' => 'active',
                'last_login_at' => now()
            ]);

            return $json['data'] ?? [];

        } catch (\Exception $e) {
            // General exception logging
            Log::error("Automation Service Exception during {$action}: " . $e->getMessage());

            $hasScreenshot = file_exists($screenshotPath);
            AutomationLog::create([
                'platform' => $platform,
                'auction_id' => $auctionId,
                'user_id' => $userId,
                'action' => $action,
                'status' => 'failed',
                'message' => $e->getMessage(),
                'payload' => [
                    'url' => $args['url'] ?? null,
                    'amount' => $args['amount'] ?? null,
                    'trace' => substr($e->getTraceAsString(), 0, 500)
                ],
                'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null
            ]);

            $account->update([
                'status' => 'error',
                'last_error' => $e->getMessage()
            ]);

            throw $e;
        } finally {
            if (!empty($payloadPath) && file_exists($payloadPath)) {
                @unlink($payloadPath);
            }
        }
    }
}
