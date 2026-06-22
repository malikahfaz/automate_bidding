<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automation Mock Mode
    |--------------------------------------------------------------------------
    |
    | When true, Playwright adapters return simulated data instead of scraping
    | live platform pages. Set to false for production / real bidding.
    |
    */
    'mock_mode' => env('AUTOMATION_MOCK_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Sync & Proxy Intervals (seconds)
    |--------------------------------------------------------------------------
    */
    'sync_interval' => (int) env('AUTOMATION_SYNC_INTERVAL', 10),
    'proxy_interval' => (int) env('AUTOMATION_PROXY_INTERVAL', 5),

    /*
    |--------------------------------------------------------------------------
    | Verbose automation logging
    |--------------------------------------------------------------------------
    |
    | When false (default), background jobs do not write to laravel.log or
    | automation_logs on every sync/bid. Failures are still recorded.
    |
    */
    'verbose_log' => env('AUTOMATION_VERBOSE_LOG', false),

];
