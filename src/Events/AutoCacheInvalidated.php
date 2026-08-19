<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Events;

final class AutoCacheInvalidated
{
    /**
     * @param  'record'|'table'  $scope
     */
    public function __construct(
        public string $table,
        public string $scope,
        public int|string|null $recordId = null,
        /** @var list<string> */
        public array $keys = [],
    ) {}
}
