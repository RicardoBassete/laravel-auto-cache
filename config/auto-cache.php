<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Null uses the application's default cache store. Any Cache::store()
    | driver is supported (array, file, redis, database, etc.).
    |
    */

    'store' => env('AUTO_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Default TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | Used when the model does not set $cacheTtl. Default: 1 hour.
    |
    */

    'ttl' => (int) env('AUTO_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Key Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('AUTO_CACHE_PREFIX', 'auto-cache'),

    /*
    |--------------------------------------------------------------------------
    | Registry Lock
    |--------------------------------------------------------------------------
    |
    | Seconds to hold the registry lock when the cache store supports locks.
    | Falls back to best-effort merge when locking is unavailable.
    |
    */

    'lock_seconds' => (int) env('AUTO_CACHE_LOCK_SECONDS', 5),

    /*
    |--------------------------------------------------------------------------
    | Request Collector (Telescope / Debugbar)
    |--------------------------------------------------------------------------
    |
    | When enabled, AutoCacheHit / Miss / Invalidated events are buffered on
    | AutoCacheCollector for the current request. Optional bridges push the
    | same data into Laravel Telescope (Cache watcher) and Barryvdh Debugbar
    | when those packages are installed.
    |
    */

    'collector' => [
        'enabled' => (bool) env('AUTO_CACHE_COLLECTOR', false),
        'telescope' => (bool) env('AUTO_CACHE_TELESCOPE', true),
        'debugbar' => (bool) env('AUTO_CACHE_DEBUGBAR', true),
    ],

];
