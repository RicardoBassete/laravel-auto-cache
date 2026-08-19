<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Eloquent;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RicardoBassete\AutoCache\CacheContext;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

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

    public function cachingEnabled(): bool
    {
        return $this->cachingEnabled && ! CacheContext::suppressed();
    }

    /**
     * @param  array<int, TModel>  $models
     * @return array<int, TModel>
     */
    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        /** @var array<int, TModel> $loaded */
        $loaded = CacheContext::withoutCaching(
            fn (): array => parent::eagerLoadRelation($models, $name, $constraints),
        );

        return $loaded;
    }

    /**
     * @param  array<int, mixed>|Arrayable<int, mixed>|int|string  $id
     * @param  list<string>  $columns
     * @return ($id is array ? EloquentCollection<int, TModel> : TModel|null)
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id) || $id instanceof Arrayable) {
            return parent::find($id, $columns);
        }

        if (! $this->cachingEnabled()) {
            return parent::find($id, $columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $manager->recordKey($model->getTable(), $id, $eager);

        [$hit, $cached] = $manager->attempt($key, $model->getTable(), $id);

        if ($hit) {
            /** @var TModel|null $cached */

            return $cached;
        }

        $result = $this->runWithoutCaching(fn () => parent::find($id, $columns));

        /** @var TModel|null $modelResult */
        $modelResult = is_object($result) || $result === null ? $result : null;

        if ($this->shouldStore($modelResult)) {
            $manager->put($key, $modelResult, $this->ttl(), $model->getTable(), $id);
        }

        return $modelResult;
    }

    /**
     * @param  Arrayable<array-key, mixed>|array<mixed>  $ids
     * @param  list<string>  $columns
     * @return EloquentCollection<int, TModel>
     */
    public function findMany($ids, $columns = ['*'])
    {
        $ids = $ids instanceof Arrayable ? $ids->toArray() : $ids;

        if ($ids === []) {
            return $this->model->newCollection();
        }

        if (! $this->cachingEnabled()) {
            return parent::findMany($ids, $columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $table = $model->getTable();

        /** @var array<string|int, TModel|null> $resolved */
        $resolved = [];
        $missing = [];

        foreach ($ids as $id) {
            if (! is_int($id) && ! is_string($id)) {
                $missing[] = $id;

                continue;
            }

            $key = $manager->recordKey($table, $id, $eager);

            [$hit, $cached] = $manager->attempt($key, $table, $id);

            if ($hit) {
                /** @var TModel|null $cached */
                $resolved[$id] = $cached;

                continue;
            }

            $missing[] = $id;
        }

        if ($missing !== []) {
            /** @var EloquentCollection<int, TModel> $loaded */
            $loaded = $this->runWithoutCaching(fn (): EloquentCollection => parent::findMany($missing, $columns));

            foreach ($loaded as $item) {
                $itemKey = $item->getKey();

                if (! is_int($itemKey) && ! is_string($itemKey)) {
                    continue;
                }

                $cacheKey = $manager->recordKey($table, $itemKey, $eager);

                if ($this->shouldStore($item)) {
                    $manager->put($cacheKey, $item, $this->ttl(), $table, $itemKey);
                }

                $resolved[$itemKey] = $item;
            }

            foreach ($missing as $id) {
                if (! is_int($id) && ! is_string($id)) {
                    continue;
                }

                if (array_key_exists($id, $resolved)) {
                    continue;
                }

                if ($this->shouldStore(null)) {
                    $cacheKey = $manager->recordKey($table, $id, $eager);
                    $manager->put($cacheKey, null, $this->ttl(), $table, $id);
                }

                $resolved[$id] = null;
            }
        }

        $models = [];

        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || ! array_key_exists($id, $resolved)) {
                continue;
            }

            $item = $resolved[$id];

            if ($item instanceof Model) {
                $models[] = $item;
            }
        }

        return $this->model->newCollection($models);
    }

    /**
     * @param  list<string>  $columns
     * @return EloquentCollection<int, TModel>
     */
    public function get($columns = ['*']): EloquentCollection
    {
        if (! $this->cachingEnabled()) {
            return parent::get($columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $this->resolveCollectionKey($manager, $model, $eager);

        [$hit, $cached] = $manager->attempt($key, $model->getTable());

        if ($hit) {
            /** @var EloquentCollection<int, TModel> $cached */

            return $cached;
        }

        /** @var EloquentCollection<int, TModel> $result */
        $result = $this->runWithoutCaching(fn (): EloquentCollection => parent::get($columns));

        if ($this->shouldStore($result)) {
            $manager->put($key, $result, $this->ttl(), $model->getTable(), null);
        }

        return $result;
    }

    /**
     * @param  list<string>  $columns
     * @return TModel|null
     */
    public function first($columns = ['*'])
    {
        if (! $this->cachingEnabled()) {
            return parent::first($columns);
        }

        $manager = $this->manager();
        $model = $this->getModel();
        $eager = $this->eagerNames();
        $key = $this->resolveQueryKey($manager, $model, $eager, 'first');

        [$hit, $cached] = $manager->attempt($key, $model->getTable());

        if ($hit) {
            /** @var TModel|null $cached */

            return $cached;
        }

        /** @var TModel|null $result */
        $result = $this->runWithoutCaching(fn () => parent::first($columns));

        if ($this->shouldStore($result)) {
            $recordId = $result?->getKey();
            $manager->put(
                $key,
                $result,
                $this->ttl(),
                $model->getTable(),
                is_int($recordId) || is_string($recordId) ? $recordId : null,
            );
        }

        return $result;
    }

    public function value($column): mixed
    {
        if (! $this->cachingEnabled()) {
            return parent::value($column);
        }

        return $this->rememberScalar('value:'.$this->stringifyColumn($column), fn (): mixed => parent::value($column));
    }

    /**
     * @param  string|Expression  $column
     * @param  string|null  $key
     * @return Collection<array-key, mixed>
     */
    public function pluck($column, $key = null): Collection
    {
        if (! $this->cachingEnabled()) {
            return parent::pluck($column, $key);
        }

        $suffix = 'pluck:'.$this->stringifyColumn($column).':'.($key ?? '');

        /** @var Collection<array-key, mixed> $result */
        $result = $this->rememberScalar($suffix, fn (): Collection => parent::pluck($column, $key));

        return $result;
    }

    /**
     * @param  Expression|string  $columns
     */
    public function count($columns = '*'): int
    {
        if (! $this->cachingEnabled()) {
            return (int) $this->toBase()->count($columns);
        }

        $counted = $this->rememberScalar(
            'count:'.$this->stringifyColumn($columns),
            fn (): mixed => $this->toBase()->count($columns),
        );

        return is_numeric($counted) ? (int) $counted : 0;
    }

    /**
     * @param  Expression|string  $column
     */
    public function sum($column): mixed
    {
        if (! $this->cachingEnabled()) {
            return $this->toBase()->sum($column);
        }

        return $this->rememberScalar(
            'sum:'.$this->stringifyColumn($column),
            fn (): mixed => $this->toBase()->sum($column),
        );
    }

    public function exists(): bool
    {
        if (! $this->cachingEnabled()) {
            return $this->toBase()->exists();
        }

        return (bool) $this->rememberScalar(
            'exists',
            fn (): bool => $this->toBase()->exists(),
        );
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
        $result = $this->toBase()->insert($values);
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

        if ($model instanceof AutoCacheable) {
            return $model->cacheTtlSeconds();
        }

        return $this->manager()->defaultTtl();
    }

    protected function shouldStore(mixed $result): bool
    {
        $model = $this->getModel();
        $cacheMisses = $model instanceof AutoCacheable
            ? $model->shouldCacheMisses()
            : false;

        if ($result === null || $result === false) {
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
        /** @var array<int|string, mixed> $bindings */
        $bindings = $base->getBindings();

        return $manager->queryKey($model->getTable(), $sql, $bindings, $eager);
    }

    protected function isFullTableQuery(): bool
    {
        $query = $this->getQuery();

        return $query->wheres === []
            && ($query->joins === [] || $query->joins === null)
            && blank($query->groups)
            && blank($query->havings)
            && blank($query->orders)
            && $query->limit === null
            && $query->offset === null;
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function runWithoutCaching(callable $callback): mixed
    {
        $previous = $this->cachingEnabled;
        $this->cachingEnabled = false;

        try {
            return $callback();
        } finally {
            $this->cachingEnabled = $previous;
        }
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

        [$hit, $cached] = $manager->attempt($key, $model->getTable());

        if ($hit) {
            return $cached;
        }

        $result = $this->runWithoutCaching($callback);

        if ($this->shouldStore($result)) {
            $manager->put($key, $result, $this->ttl(), $model->getTable(), null);
        }

        return $result;
    }

    protected function invalidateAfterMassMutation(): void
    {
        // Single-row model saves (performUpdate / soft delete) also call
        // Builder::update/delete with a primary-key where. Model events already
        // invalidate that record — skip mass flush here.
        if ($this->isSingleRecordMutationQuery()) {
            return;
        }

        $model = $this->getModel();
        $tables = [$model->getTable()];

        if ($model instanceof AutoCacheable) {
            $tables = array_values(array_unique([
                ...$tables,
                ...$model->cacheInvalidatesTables(),
            ]));
        }

        $this->manager()->invalidateTables($tables);
    }

    /**
     * Detect PK-equality mutations produced by Model::performUpdate / SoftDeletes.
     */
    protected function isSingleRecordMutationQuery(): bool
    {
        $model = $this->getModel();
        $keyName = $model->getKeyName();
        $qualifiedKeyName = $model->getQualifiedKeyName();
        $wheres = $this->getQuery()->wheres;

        $relevant = [];

        foreach ($wheres as $where) {
            if (! is_array($where)) {
                continue;
            }

            $type = $where['type'] ?? '';
            $column = $where['column'] ?? '';

            if ($type === 'Null' && is_string($column) && str_ends_with($column, 'deleted_at')) {
                continue;
            }

            $relevant[] = $where;
        }

        if (count($relevant) !== 1) {
            return false;
        }

        $where = $relevant[0];
        $column = $where['column'] ?? null;
        $operator = $where['operator'] ?? null;
        $value = $where['value'] ?? null;

        return ($where['type'] ?? null) === 'Basic'
            && ($column === $keyName || $column === $qualifiedKeyName)
            && $operator === '='
            && ! is_array($value);
    }

    protected function stringifyColumn(mixed $column): string
    {
        if ($column instanceof Expression) {
            $value = $column->getValue($this->getGrammar());

            return is_string($value) ? $value : md5(serialize($value));
        }

        if (is_string($column)) {
            return $column;
        }

        return md5(serialize($column));
    }
}
