<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use Throwable;

final class CacheManager
{
    public function store(): Repository
    {
        $name = config('auto-cache.store');

        return $name ? Cache::store($name) : Cache::store();
    }

    public function prefix(): string
    {
        return (string) config('auto-cache.prefix', 'auto-cache');
    }

    public function defaultTtl(): int
    {
        return (int) config('auto-cache.ttl', 3600);
    }

    public function lockSeconds(): int
    {
        return (int) config('auto-cache.lock_seconds', 5);
    }

    /**
     * @param  list<string>  $eager
     */
    public function recordKey(string $table, int|string $id, array $eager = []): string
    {
        return $this->prefix().':'.$table.':id:'.$id.':with:'.$this->eagerHash($eager);
    }

    /**
     * @param  list<string>  $eager
     */
    public function tableKey(string $table, array $eager = []): string
    {
        return $this->prefix().':'.$table.':all:with:'.$this->eagerHash($eager);
    }

    /**
     * @param  list<string>  $eager
     */
    public function queryKey(string $table, string $sql, array $bindings, array $eager = []): string
    {
        $hash = hash('xxh128', $sql.'|'.serialize($bindings).'|'.$this->eagerHash($eager));

        return $this->prefix().':'.$table.':query:'.$hash;
    }

    /**
     * @param  list<string>  $eager
     */
    public function eagerHash(array $eager): string
    {
        $normalized = $eager;
        sort($normalized);

        return $normalized === [] ? 'none' : hash('xxh128', implode(',', $normalized));
    }

    public function remember(string $key, int $ttl, string $table, int|string|null $recordId, callable $callback): mixed
    {
        $store = $this->store();

        if ($store->has($key)) {
            return $store->get($key);
        }

        $value = $callback();

        $store->put($key, $value, $ttl);
        $this->registerKey($table, $key, $recordId);

        return $value;
    }

    public function put(string $key, mixed $value, int $ttl, string $table, int|string|null $recordId): void
    {
        $this->store()->put($key, $value, $ttl);
        $this->registerKey($table, $key, $recordId);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function get(string $key): mixed
    {
        return $this->store()->get($key);
    }

    public function forget(string $key): void
    {
        $this->store()->forget($key);
    }

    public function registerKey(string $table, string $key, int|string|null $recordId = null): void
    {
        $this->mutateRegistry($this->tableRegistryKey($table), function (array $keys) use ($key): array {
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }

            return $keys;
        });

        if ($recordId !== null) {
            $this->mutateRegistry($this->recordRegistryKey($table, $recordId), function (array $keys) use ($key): array {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }

                return $keys;
            });
        }
    }

    public function invalidateRecord(string $table, int|string $id): void
    {
        $this->runAfterCommit(function () use ($table, $id): void {
            $registryKey = $this->recordRegistryKey($table, $id);
            $keys = $this->readRegistry($registryKey);

            foreach ($keys as $key) {
                $this->store()->forget($key);
            }

            $this->store()->forget($registryKey);
            $this->removeKeysFromTableRegistry($table, $keys);
        });
    }

    public function invalidateTable(string $table): void
    {
        $this->runAfterCommit(function () use ($table): void {
            $registryKey = $this->tableRegistryKey($table);
            $keys = $this->readRegistry($registryKey);

            foreach ($keys as $key) {
                $this->store()->forget($key);
            }

            $this->store()->forget($registryKey);
        });
    }

    /**
     * @param  list<string>  $tables
     */
    public function invalidateTables(array $tables): void
    {
        foreach (array_unique($tables) as $table) {
            $this->invalidateTable($table);
        }
    }

    public function invalidateModel(Model $model, bool $singleRecord = true): void
    {
        $table = $model->getTable();
        /** @var list<string> $cascade */
        $cascade = [];

        if (in_array(AutoCaches::class, class_uses_recursive($model), true)) {
            /** @var Model&object{cacheInvalidatesTables(): list<string>} $model */
            $cascade = $model->cacheInvalidatesTables();
        }

        $this->runAfterCommit(function () use ($model, $table, $cascade, $singleRecord): void {
            if ($singleRecord && $model->getKey() !== null) {
                $this->invalidateRecordNow($table, $model->getKey());
            } else {
                $this->invalidateTableNow($table);
            }

            foreach ($cascade as $relatedTable) {
                $this->invalidateTableNow($relatedTable);
            }
        });
    }

    public function tableRegistryKey(string $table): string
    {
        return $this->prefix().':registry:table:'.$table;
    }

    public function recordRegistryKey(string $table, int|string $id): string
    {
        return $this->prefix().':registry:table:'.$table.':id:'.$id;
    }

    /**
     * @return list<string>
     */
    public function readRegistry(string $registryKey): array
    {
        $keys = $this->store()->get($registryKey, []);

        if (! is_array($keys)) {
            return [];
        }

        /** @var list<string> $normalized */
        $normalized = array_values(array_filter($keys, is_string(...)));

        return $normalized;
    }

    private function invalidateRecordNow(string $table, int|string $id): void
    {
        $registryKey = $this->recordRegistryKey($table, $id);
        $keys = $this->readRegistry($registryKey);

        foreach ($keys as $key) {
            $this->store()->forget($key);
        }

        $this->store()->forget($registryKey);
        $this->removeKeysFromTableRegistry($table, $keys);
    }

    private function invalidateTableNow(string $table): void
    {
        $registryKey = $this->tableRegistryKey($table);
        $keys = $this->readRegistry($registryKey);

        foreach ($keys as $key) {
            $this->store()->forget($key);
        }

        $this->store()->forget($registryKey);
    }

    /**
     * @param  list<string>  $keysToRemove
     */
    private function removeKeysFromTableRegistry(string $table, array $keysToRemove): void
    {
        if ($keysToRemove === []) {
            return;
        }

        $this->mutateRegistry($this->tableRegistryKey($table), function (array $keys) use ($keysToRemove): array {
            return array_values(array_filter(
                $keys,
                static fn (string $key): bool => ! in_array($key, $keysToRemove, true),
            ));
        });
    }

    /**
     * @param  callable(list<string>): list<string>  $callback
     */
    private function mutateRegistry(string $registryKey, callable $callback): void
    {
        $store = $this->store();
        $ttl = $this->defaultTtl();

        $apply = function () use ($store, $registryKey, $callback, $ttl): void {
            /** @var list<string> $current */
            $current = $this->readRegistry($registryKey);
            $updated = $callback($current);
            $store->put($registryKey, $updated, $ttl);
        };

        try {
            $lock = $store->lock($registryKey.':lock', $this->lockSeconds());
            $lock->block($this->lockSeconds(), $apply);
        } catch (Throwable) {
            $apply();
        }
    }

    private function runAfterCommit(callable $callback): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);

            return;
        }

        $callback();
    }
}
