<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Contracts;

interface AutoCacheable
{
    public function cacheTtlSeconds(): int;

    public function shouldCacheMisses(): bool;

    /**
     * @return list<string>
     */
    public function cacheInvalidatesTables(): array;

    /**
     * @return list<string>
     */
    public function cacheSilentAttributesList(): array;
}
