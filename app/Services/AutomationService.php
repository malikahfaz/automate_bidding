<?php

namespace App\Services;

use App\Models\PlatformAccount;
use App\Models\AutomationLog;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AutomationService
{
    protected function verboseLog(): bool
    {
        return (bool) config('automation.verbose_log', false);
    }

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
        $account = PlatformAccount::where('platform', $platform)->first();
        if (!$account) {
            throw new \Exception("No master account found for platform: {$platform}");
        }

        $email = $account->email;
        $password = $account->getDecryptedPassword();

        $cliPath = base_path('automation/cli.cjs');
        $cookiesPath = storage_path("app/automation/cookies_{$platform}.json");

        $screenshotName = "{$platform}_{$action}_" . time() . ".png";
        $screenshotPath = storage_path("app/public/automation/screenshots/{$screenshotName}");
        $screenshotPublicUrl = "storage/automation/screenshots/{$screenshotName}";

        if (!file_exists(dirname($cookiesPath))) {
            mkdir(dirname($cookiesPath), 0755, true);
        }
        if (!file_exists(dirname($screenshotPath))) {
            mkdir(dirname($screenshotPath), 0755, true);
        }

        $automationTmp = storage_path('app/automation/tmp');
        if (!is_dir($automationTmp)) {
            mkdir($automationTmp, 0755, true);
        }

        $mockMode = (bool) config('automation.mock_mode', false);
        $verbose = $this->verboseLog();

        $cmd = [
            'node',
            $cliPath,
            '--action', $action,
            '--platform', $platform,
            '--email', $email,
            '--password', $password,
            '--cookies-path', $cookiesPath,
            '--screenshot-path', $screenshotPath,
        ];

        if ($verbose) {
            $cmd[] = '--verbose-log';
            $cmd[] = 'true';
        }

        if (isset($args['url'])) {
            $cmd[] = '--url';
            $cmd[] = $args['url'];
        }

        if (isset($args['amount'])) {
            $cmd[] = '--amount';
            $cmd[] = (string) $args['amount'];
        }

        if (!empty($args['lot_id'])) {
            $cmd[] = '--lot-id';
            $cmd[] = (string) $args['lot_id'];
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

        $process = new Process($cmd, base_path(), [
            'TEMP' => $automationTmp,
            'TMP' => $automationTmp,
            'TMPDIR' => $automationTmp,
        ]);
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

        if ($verbose) {
            Log::info("Starting automation process: {$action} for platform {$platform}", [
                'url' => $args['url'] ?? null,
                'lot_id' => $args['lot_id'] ?? null,
                'amount' => $args['amount'] ?? null,
            ]);
        }

        try {
            $process->run();
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            if ($verbose) {
                Log::debug('Automation process output: ' . $output);
                if (!empty($errorOutput)) {
                    Log::info("Automation [{$action}] stderr", [
                        'platform' => $platform,
                        'lot_id' => $args['lot_id'] ?? null,
                        'stderr' => $errorOutput,
                    ]);
                }
            }

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
                throw new \Exception('Automation script did not return a valid JSON response. Stderr: ' . $errorOutput);
            }

            if (isset($json['success']) && $json['success'] === false) {
                $errorMsg = $json['error'] ?? 'Unknown automation error';
                $hasScreenshot = isset($json['screenshot']) && file_exists($screenshotPath);

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
                        'lot_id' => $args['lot_id'] ?? null,
                        'stderr' => $verbose ? $errorOutput : null,
                    ],
                    'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null,
                ]);

                $account->update([
                    'status' => 'error',
                    'last_error' => $errorMsg,
                ]);

                throw new \Exception("Automation Failed: {$errorMsg}");
            }

            if ($verbose) {
                $hasScreenshot = file_exists($screenshotPath);
                AutomationLog::create([
                    'platform' => $platform,
                    'auction_id' => $auctionId,
                    'user_id' => $userId,
                    'action' => $action,
                    'status' => 'success',
                    'message' => "Successfully completed action '{$action}'",
                    'payload' => $json['data'] ?? null,
                    'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null,
                ]);
            }

            $account->update([
                'status' => 'active',
                'last_login_at' => now(),
            ]);

            return $json['data'] ?? [];

        } catch (\Exception $e) {
            if (!$verbose || !str_contains($e->getMessage(), 'Automation Failed:')) {
                Log::error("Automation Service Exception during {$action}: " . $e->getMessage());
            }

            $hasScreenshot = file_exists($screenshotPath);

            if (!str_contains($e->getMessage(), 'Automation Failed:')) {
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
                    ],
                    'screenshot_path' => $hasScreenshot ? $screenshotPublicUrl : null,
                ]);
            }

            $account->update([
                'status' => 'error',
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if (!empty($payloadPath) && file_exists($payloadPath)) {
                @unlink($payloadPath);
            }
        }
    }
}
