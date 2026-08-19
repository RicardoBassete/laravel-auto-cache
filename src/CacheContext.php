<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache;

final class CacheContext
{
    private static int $suppressionDepth = 0;

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutCaching(callable $callback): mixed
    {
        self::$suppressionDepth++;

        try {
            return $callback();
        } finally {
            self::$suppressionDepth--;
        }
    }

    public static function suppressed(): bool
    {
        return self::$suppressionDepth > 0;
    }
}
