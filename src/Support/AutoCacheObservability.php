<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Support;

use Illuminate\Support\Facades\Event;
use RicardoBassete\AutoCache\Events\AutoCacheHit;
use RicardoBassete\AutoCache\Events\AutoCacheInvalidated;
use RicardoBassete\AutoCache\Events\AutoCacheMiss;

/**
 * Wires AutoCache events to the in-request collector and optional Telescope recording.
 *
 * @internal
 */
final class AutoCacheObservability
{
    public static function register(AutoCacheCollector $collector): void
    {
        Event::listen(AutoCacheHit::class, static function (AutoCacheHit $event) use ($collector): void {
            $collector->recordHit($event);
            TelescopeBridge::record('hit', $event->key, $event->table, $event->recordId);
        });

        Event::listen(AutoCacheMiss::class, static function (AutoCacheMiss $event) use ($collector): void {
            $collector->recordMiss($event);
            TelescopeBridge::record('miss', $event->key, $event->table, $event->recordId);
        });

        Event::listen(AutoCacheInvalidated::class, static function (AutoCacheInvalidated $event) use ($collector): void {
            $collector->recordInvalidated($event);
            TelescopeBridge::record(
                'forget',
                $event->scope.':'.$event->table,
                $event->table,
                $event->recordId,
            );
        });
    }
}
