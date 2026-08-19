<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Support;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

/**
 * Optional Laravel Telescope bridge (loaded only when Telescope is installed).
 *
 * @internal
 */
final class TelescopeBridge
{
    public static function record(
        string $type,
        string $key,
        string $table,
        int|string|null $recordId,
    ): void {
        if (! (bool) config('auto-cache.collector.telescope', true)) {
            return;
        }

        if (! class_exists(Telescope::class)
            || ! class_exists(IncomingEntry::class)) {
            return;
        }

        if (! Telescope::isRecording()) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => $type,
            'key' => $key,
            'value' => [
                'table' => $table,
                'record_id' => $recordId,
                'source' => 'auto-cache',
            ],
            'expiration' => null,
        ]));
    }
}
