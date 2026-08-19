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
 * @property list<string> $cacheSilentAttributes
 * @property bool $cacheFlushListsOnSave
 */
trait AutoCaches
{
    public static function bootAutoCaches(): void
    {
        static::created(static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true);
        });

        static::updated(static function (Model $model): void {
            static::autoCacheInvalidateModel($model, singleRecord: true, respectSilentAttributes: true);
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

    /**
     * Forget cached find entries for a primary key (and cascade tables).
     */
    public static function autoCacheForget(int|string $id): void
    {
        /** @var static&Model&AutoCacheable $model */
        $model = new static;
        $manager = app(CacheManager::class);

        $manager->invalidateRecord($model->getTable(), $id);
        $manager->invalidateTables($model->cacheInvalidatesTables());
    }

    /**
     * Flush all registered cache keys for this model's table (and cascade tables).
     */
    public static function autoCacheFlush(): void
    {
        /** @var static&Model&AutoCacheable $model */
        $model = new static;
        $manager = app(CacheManager::class);

        $manager->invalidateTables([
            $model->getTable(),
            ...$model->cacheInvalidatesTables(),
        ]);
    }

    /**
     * Flush list/query/aggregation cache keys for this model's table (find keys stay).
     */
    public static function autoCacheFlushLists(): void
    {
        /** @var static&Model&AutoCacheable $model */
        $model = new static;
        $manager = app(CacheManager::class);

        $manager->invalidateListKeys($model->getTable());

        foreach ($model->cacheInvalidatesTables() as $table) {
            $manager->invalidateListKeys($table);
        }
    }

    /**
     * Forget cached find entries for this model instance.
     */
    public function autoCacheForgetSelf(): void
    {
        $key = $this->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return;
        }

        static::autoCacheForget($key);
    }

    /**
     * List registered cache keys for this model's table (debug / introspection).
     *
     * @return list<string>
     */
    public static function autoCacheKeys(): array
    {
        /** @var static&Model $model */
        $model = new static;
        $manager = app(CacheManager::class);

        return $manager->readRegistry($manager->tableRegistryKey($model->getTable()));
    }

    /**
     * Remember a value under the find/record cache key for the given id.
     *
     * @param  list<string>  $eager
     */
    public static function autoCacheRemember(int|string $id, callable $callback, array $eager = []): mixed
    {
        /** @var static&Model&AutoCacheable $model */
        $model = new static;
        $manager = app(CacheManager::class);
        $key = $manager->recordKey($model->getTable(), $id, $eager);

        return $manager->remember(
            $key,
            $model->cacheTtlSeconds(),
            $model->getTable(),
            $id,
            $callback,
        );
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

    public function shouldFlushListsOnSave(): bool
    {
        if (! property_exists($this, 'cacheFlushListsOnSave')) {
            return false;
        }

        return (bool) $this->cacheFlushListsOnSave;
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

    /**
     * @return list<string>
     */
    public function cacheSilentAttributesList(): array
    {
        if (! property_exists($this, 'cacheSilentAttributes')) {
            return [];
        }

        $attributes = [];

        foreach ((array) $this->cacheSilentAttributes as $attribute) {
            $attributes[] = (string) $attribute;
        }

        return $attributes;
    }

    protected static function autoCacheInvalidateModel(
        Model $model,
        bool $singleRecord,
        bool $respectSilentAttributes = false,
    ): void {
        if ($respectSilentAttributes && static::autoCacheShouldSkipInvalidation($model)) {
            return;
        }

        app(CacheManager::class)->invalidateModel($model, $singleRecord);
    }

    protected static function autoCacheShouldSkipInvalidation(Model $model): bool
    {
        if (! $model instanceof AutoCacheable) {
            return false;
        }

        $silent = $model->cacheSilentAttributesList();

        if ($silent === []) {
            return false;
        }

        $changed = array_keys($model->getChanges());

        if ($changed === []) {
            return false;
        }

        foreach ($changed as $attribute) {
            if (! in_array($attribute, $silent, true)) {
                return false;
            }
        }

        return true;
    }
}
