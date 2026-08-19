<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache;

final class CacheEntry
{
    public function __construct(public mixed $value) {}
}
