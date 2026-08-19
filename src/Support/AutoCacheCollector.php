<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Support;

use RicardoBassete\AutoCache\Events\AutoCacheHit;
use RicardoBassete\AutoCache\Events\AutoCacheInvalidated;
use RicardoBassete\AutoCache\Events\AutoCacheMiss;

final class AutoCacheCollector
{
    /**
     * @var list<array{
     *     type: 'hit'|'miss'|'invalidated',
     *     table: string,
     *     key: string|null,
     *     record_id: int|string|null,
     *     scope: string|null,
     *     keys: list<string>
     * }>
     */
    private array $entries = [];

    public function enabled(): bool
    {
        return (bool) config('auto-cache.collector.enabled', false);
    }

    public function recordHit(AutoCacheHit $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->entries[] = [
            'type' => 'hit',
            'table' => $event->table,
            'key' => $event->key,
            'record_id' => $event->recordId,
            'scope' => null,
            'keys' => [],
        ];
    }

    public function recordMiss(AutoCacheMiss $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->entries[] = [
            'type' => 'miss',
            'table' => $event->table,
            'key' => $event->key,
            'record_id' => $event->recordId,
            'scope' => null,
            'keys' => [],
        ];
    }

    public function recordInvalidated(AutoCacheInvalidated $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->entries[] = [
            'type' => 'invalidated',
            'table' => $event->table,
            'key' => null,
            'record_id' => $event->recordId,
            'scope' => $event->scope,
            'keys' => $event->keys,
        ];
    }

    /**
     * @return list<array{
     *     type: 'hit'|'miss'|'invalidated',
     *     table: string,
     *     key: string|null,
     *     record_id: int|string|null,
     *     scope: string|null,
     *     keys: list<string>
     * }>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    public function hits(): int
    {
        return count(array_filter($this->entries, static fn (array $e): bool => $e['type'] === 'hit'));
    }

    public function misses(): int
    {
        return count(array_filter($this->entries, static fn (array $e): bool => $e['type'] === 'miss'));
    }

    public function invalidations(): int
    {
        return count(array_filter($this->entries, static fn (array $e): bool => $e['type'] === 'invalidated'));
    }

    public function flush(): void
    {
        $this->entries = [];
    }
}
