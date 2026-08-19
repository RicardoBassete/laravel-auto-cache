<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Eloquent\CachedBuilder;

/**
 * @mixin Model
 *
 * @property int|null $cacheTtl
 * @property bool $cacheMisses
 * @property list<string> $cacheInvalidates
 */
trait AutoCaches
{
    public static function bootAutoCaches(): void
    {
        static::created(static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });

        static::updated(static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });

        static::deleted(static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });

        // SoftDeletes events — registered even without the trait; they simply never fire.
        static::registerModelEvent('restored', static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });

        static::registerModelEvent('forceDeleted', static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return CachedBuilder<static>
     */
    public function newEloquentBuilder($query): CachedBuilder
    {
        return new CachedBuilder($query);
    }

    /**
     * @return CachedBuilder<static>
     */
    public static function withoutCache(): CachedBuilder
    {
        /** @var CachedBuilder<static> $builder */
        $builder = static::query();

        return $builder->withoutCache();
    }

    public function cacheTtlSeconds(): int
    {
        if (property_exists($this, 'cacheTtl') && $this->cacheTtl !== null) {
            return (int) $this->cacheTtl;
        }

        return app(CacheManager::class)->defaultTtl();
    }

    public function shouldCacheMisses(): bool
    {
        if (property_exists($this, 'cacheMisses')) {
            return (bool) $this->cacheMisses;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function cacheInvalidatesTables(): array
    {
        if (! property_exists($this, 'cacheInvalidates')) {
            return [];
        }

        /** @var list<string> $tables */
        $tables = array_values(array_map(strval(...), (array) $this->cacheInvalidates));

        return $tables;
    }

    protected static function autoCacheInvalidateModel(Model $model, bool $singleRecord): void
    {
        app(CacheManager::class)->invalidateModel($model, $singleRecord);
    }
}
