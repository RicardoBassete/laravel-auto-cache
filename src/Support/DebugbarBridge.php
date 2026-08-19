<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Support;

use Barryvdh\Debugbar\LaravelDebugbar;
use RicardoBassete\AutoCache\Debugbar\AutoCacheDebugbarCollector;

/**
 * Registers the optional Debugbar panel when barryvdh/laravel-debugbar is present.
 *
 * @internal
 */
final class DebugbarBridge
{
    public static function register(AutoCacheCollector $collector): void
    {
        if (! (bool) config('auto-cache.collector.debugbar', true)) {
            return;
        }

        if (! $collector->enabled()) {
            return;
        }

        if (! class_exists(LaravelDebugbar::class)
            || ! class_exists(AutoCacheDebugbarCollector::class)) {
            return;
        }

        try {
            $debugbar = app(LaravelDebugbar::class);
        } catch (\Throwable) {
            return;
        }

        if (! is_object($debugbar) || ! method_exists($debugbar, 'addCollector')) {
            return;
        }

        $debugbar->addCollector(new AutoCacheDebugbarCollector($collector));
    }
}
