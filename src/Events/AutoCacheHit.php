<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Events;

final class AutoCacheHit
{
    public function __construct(
        public string $key,
        public string $table,
        public int|string|null $recordId = null,
    ) {}
}
