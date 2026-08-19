<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Concerns\AutoCaches;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class CachedBuilder extends Builder
{
    protected bool $cachingEnabled = true;

    public function withoutCache(): static
    {
        $this->cachingEnabled = false;

        return $this;
    }

    public function withCache(): static
    {
        $this->cachingEnabled = true;

        return $this;
    }

    /**
     * @param  array<int, mixed>|int|string  $id
     * @param  list<string>  $columns
     */
    public function find($id, $columns = ['*']): Model|EloquentCollection|null
    {
        if (! $this->cachingEnabled || $this->isWriteQuery() || is_array($id)) {
            return parent::find($id, $columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $manager->recordKey($model->getTable(), $id, $eager);

        if ($manager->has($key)) {
            /** @var Model|null $cached */
            $cached = $manager->get($key);

            return $cached;
        }

        /** @var Model|null $result */
        $result = parent::find($id, $columns);

        if ($this->shouldStore($result)) {
            $manager->put($key, $result, $this->ttl(), $model->getTable(), $id);
        }

        return $result;
    }

    /**
     * @param  list<string>  $columns
     * @return EloquentCollection<int, TModel>
     */
    public function get($columns = ['*']): EloquentCollection
    {
        if (! $this->cachingEnabled || $this->isWriteQuery()) {
            return parent::get($columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $this->resolveCollectionKey($manager, $model, $eager);

        if ($manager->has($key)) {
            /** @var EloquentCollection<int, TModel> $cached */
            $cached = $manager->get($key);

            return $cached;
        }

        $result = parent::get($columns);

        if ($this->shouldStore($result)) {
            $manager->put($key, $result, $this->ttl(), $model->getTable(), null);
        }

        return $result;
    }

    /**
     * @param  list<string>  $columns
     */
    public function first($columns = ['*']): ?Model
    {
        if (! $this->cachingEnabled || $this->isWriteQuery()) {
            return parent::first($columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $this->resolveQueryKey($manager, $model, $eager, 'first');

        if ($manager->has($key)) {
            /** @var Model|null $cached */
            $cached = $manager->get($key);

            return $cached;
        }

        $result = parent::first($columns);

        if ($this->shouldStore($result)) {
            $recordId = $result?->getKey();
            $manager->put($key, $result, $this->ttl(), $model->getTable(), $recordId);
        }

        return $result;
    }

    public function value($column): mixed
    {
        if (! $this->cachingEnabled) {
            return parent::value($column);
        }

        return $this->rememberScalar('value:'.$column, fn (): mixed => parent::value($column));
    }

    /**
     * @param  string|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  string|null  $key
     * @return Collection<array-key, mixed>
     */
    public function pluck($column, $key = null): Collection
    {
        if (! $this->cachingEnabled) {
            return parent::pluck($column, $key);
        }

        $suffix = 'pluck:'.(string) $column.':'.(string) $key;

        /** @var Collection<array-key, mixed> $result */
        $result = $this->rememberScalar($suffix, fn (): Collection => parent::pluck($column, $key));

        return $result;
    }

    /**
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     */
    public function aggregate($function, $columns = ['*']): mixed
    {
        if (! $this->cachingEnabled) {
            return parent::aggregate($function, $columns);
        }

        $columnList = is_array($columns) ? implode(',', $columns) : (string) $columns;
        $suffix = 'aggregate:'.$function.':'.$columnList;

        return $this->rememberScalar($suffix, fn (): mixed => parent::aggregate($function, $columns));
    }

    public function exists(): bool
    {
        if (! $this->cachingEnabled) {
            return parent::exists();
        }

        /** @var bool $result */
        $result = $this->rememberScalar('exists', fn (): bool => parent::exists());

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        $count = parent::update($values);
        $this->invalidateAfterMassMutation();

        return $count;
    }

    public function delete(): mixed
    {
        $result = parent::delete();
        $this->invalidateAfterMassMutation();

        return $result;
    }

    public function forceDelete(): mixed
    {
        $result = parent::forceDelete();
        $this->invalidateAfterMassMutation();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function insert(array $values): bool
    {
        $result = parent::insert($values);
        $this->invalidateAfterMassMutation();

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int, string>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $result = parent::upsert($values, $uniqueBy, $update);
        $this->invalidateAfterMassMutation();

        return $result;
    }

    protected function manager(): CacheManager
    {
        return app(CacheManager::class);
    }

    protected function ttl(): int
    {
        $model = $this->getModel();

        if ($this->modelUsesAutoCache($model)) {
            /** @var Model&object{cacheTtlSeconds(): int} $model */
            return $model->cacheTtlSeconds();
        }

        return $this->manager()->defaultTtl();
    }

    protected function shouldStore(mixed $result): bool
    {
        $model = $this->getModel();
        $cacheMisses = $this->modelUsesAutoCache($model)
            ? $model->shouldCacheMisses()
            : false;

        if ($result === null) {
            return $cacheMisses;
        }

        if ($result === false) {
            return $cacheMisses;
        }

        if ($result instanceof Collection && $result->isEmpty()) {
            return $cacheMisses;
        }

        return true;
    }

    /**
     * @param  list<string>  $eager
     */
    protected function resolveCollectionKey(CacheManager $manager, Model $model, array $eager): string
    {
        if ($this->isFullTableQuery()) {
            return $manager->tableKey($model->getTable(), $eager);
        }

        return $this->resolveQueryKey($manager, $model, $eager, 'get');
    }

    /**
     * @param  list<string>  $eager
     */
    protected function resolveQueryKey(CacheManager $manager, Model $model, array $eager, string $suffix): string
    {
        $base = $this->toBase();
        $sql = $base->toSql().'|'.$suffix;
        $bindings = $base->getBindings();

        return $manager->queryKey($model->getTable(), $sql, $bindings, $eager);
    }

    protected function isFullTableQuery(): bool
    {
        return $this->wheres === []
            && $this->query->wheres === []
            && $this->query->joins === null
            && blank($this->query->groups)
            && blank($this->query->havings)
            && blank($this->query->orders)
            && $this->query->limit === null
            && $this->query->offset === null;
    }

    /**
     * @return list<string>
     */
    protected function eagerNames(): array
    {
        return array_keys($this->eagerLoad);
    }

    protected function rememberScalar(string $suffix, callable $callback): mixed
    {
        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $this->resolveQueryKey($manager, $model, $eager, $suffix);

        if ($manager->has($key)) {
            return $manager->get($key);
        }

        $result = $callback();

        if ($this->shouldStore($result)) {
            $manager->put($key, $result, $this->ttl(), $model->getTable(), null);
        }

        return $result;
    }

    protected function invalidateAfterMassMutation(): void
    {
        $model = $this->getModel();
        $manager = $this->manager();
        $tables = [$model->getTable()];

        if ($this->modelUsesAutoCache($model)) {
            $tables = array_merge($tables, $model->cacheInvalidatesTables());
        }

        $manager->invalidateTables($tables);
    }

    protected function isWriteQuery(): bool
    {
        return false;
    }

    /**
     * @phpstan-assert-if-true Model&object{
     *     cacheTtlSeconds(): int,
     *     shouldCacheMisses(): bool,
     *     cacheInvalidatesTables(): list<string>
     * } $model
     */
    protected function modelUsesAutoCache(Model $model): bool
    {
        return in_array(AutoCaches::class, class_uses_recursive($model), true);
    }
}
