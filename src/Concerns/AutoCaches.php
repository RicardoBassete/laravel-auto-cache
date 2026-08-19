<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;
use RicardoBassete\AutoCache\Eloquent\CachedBuilder;

/**
 * @mixin Model
 *
 * @phpstan-require-implements AutoCacheable
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
     * @param  QueryBuilder  $query
     * @return CachedBuilder<Model>
     */
    public function newEloquentBuilder($query): CachedBuilder
    {
        return new CachedBuilder($query);
    }

    /**
     * @return CachedBuilder<Model>
     */
    public static function withoutCache(): CachedBuilder
    {
        /** @var CachedBuilder<Model> $builder */
        $builder = static::query();

        return $builder->withoutCache();
    }

    public function cacheTtlSeconds(): int
    {
        if (! property_exists($this, 'cacheTtl') || $this->cacheTtl === null) {
            return app(CacheManager::class)->defaultTtl();
        }

        return (int) $this->cacheTtl;
    }

    public function shouldCacheMisses(): bool
    {
        if (! property_exists($this, 'cacheMisses')) {
            return false;
        }

        return (bool) $this->cacheMisses;
    }

    /**
     * @return list<string>
     */
    public function cacheInvalidatesTables(): array
    {
        if (! property_exists($this, 'cacheInvalidates')) {
            return [];
        }

        $tables = [];

        foreach ((array) $this->cacheInvalidates as $table) {
            $tables[] = (string) $table;
        }

        return $tables;
    }

    protected static function autoCacheInvalidateModel(Model $model, bool $singleRecord): void
    {
        app(CacheManager::class)->invalidateModel($model, $singleRecord);
    }
}
