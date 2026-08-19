---
name: laravel-auto-cache
description: >-
  ACTIVATE when working with ricardobassete/laravel-auto-cache, AutoCaches,
  AutoCacheable, CachedBuilder, Eloquent query caching, $cacheInvalidates,
  $cacheTtl, $cacheMisses, $cacheSilentAttributes, $cacheFlushListsOnSave,
  withoutCache, autoCacheForget/Flush/FlushLists, AutoCacheCollector, or
  auto-cache:flush. Use for opt-in model caching, invalidation rules, and
  package APIs.
license: MIT
metadata:
  author: ricardobassete
---

# laravel-auto-cache

Package: `ricardobassete/laravel-auto-cache`  
Namespace: `RicardoBassete\AutoCache`  
Requires: Laravel 11+ / PHP 8.2+

## When to use which skill

| Task | Skill |
| --- | --- |
| First-time opt-in on a model | `laravel-auto-cache-opt-in` |
| Cascade invalidation tables | `laravel-auto-cache-cascade` |
| TTL or miss caching | `laravel-auto-cache-ttl-misses` |
| Bypass cache for a query | `laravel-auto-cache-bypass` |
| Silent attribute updates | `laravel-auto-cache-silent-attributes` |
| Flush lists on save | `laravel-auto-cache-flush-lists` |
| Pest expectations | `laravel-auto-cache-pest` |
| Request collector / Telescope / Debugbar | `laravel-auto-cache-collector` |
| Artisan flush | `laravel-auto-cache-artisan-flush` |
| General behavior / mental model | this skill |

Read the focused skill before editing; do not invent APIs beyond what those skills document.

## Install (consumer app)

```bash
composer require ricardobassete/laravel-auto-cache
php artisan vendor:publish --tag=auto-cache-config   # optional
```

Config (`config/auto-cache.php`):

| Key | Default | Notes |
| --- | --- | --- |
| `store` | `null` | null = app default `Cache::store()` |
| `ttl` | `3600` | seconds |
| `prefix` | `auto-cache` | bump = logical flush after deploy |
| `lock_seconds` | `5` | registry lock when store supports locks |
| `collector.enabled` | `false` | buffer hit/miss/invalidation per request |
| `collector.telescope` | `true` | bridge if Telescope installed |
| `collector.debugbar` | `true` | panel if Debugbar installed |

After requiring the package, run `php artisan boost:update` (or `boost:install`) so Boost installs these skills into the agent skills path.

## Mental model

- **Opt-in only** via `AutoCacheable` + `AutoCaches` on the model.
- Reads cached: `find` / `findMany` / `findOrFail` / `first` / `firstOrFail` / `get` / `all`, plus `count` / `exists` / `sum` / `pluck` / `value`.
- Mutations do not cache results; they invalidate.
- **Single-row** mutation → invalidate that record’s find keys only. **List/`where`/`count` caches stay stale** until TTL, mass mutation, `autoCacheFlush()`, `autoCacheFlushLists()`, cascade, or `$cacheFlushListsOnSave = true`.
- **Mass** mutation (`where(...)->update/delete/insert/upsert`) → flush all registered keys for the table (+ `$cacheInvalidates`).
- Invalidation runs **`DB::afterCommit()`** (immediate if not in a transaction).
- Eager `with` is part of the cache key; relation queries during eager load are **not** cached separately.
- Any `Cache::store()` works; no tags required (key registry + tracked-tables registry).
- `$model->refresh()` / `replicate()` do **not** invalidate auto-cache.

## Manual / debug API

| Method | Effect |
| --- | --- |
| `Model::autoCacheForget($id)` | Forget find keys for id (+ cascade tables) |
| `Model::autoCacheFlush()` | Flush all keys for the table (+ cascade) |
| `Model::autoCacheFlushLists()` | Flush list/query/aggregation keys only |
| `$model->autoCacheForgetSelf()` | Forget this instance’s PK find keys |
| `Model::autoCacheKeys()` | List registered keys for the table |
| `Model::autoCacheRemember($id, fn, $eager = [])` | Remember under the record key |

Ops: `php artisan auto-cache:flush {table?}`.

## Events (always dispatched)

- `RicardoBassete\AutoCache\Events\AutoCacheHit`
- `RicardoBassete\AutoCache\Events\AutoCacheMiss`
- `RicardoBassete\AutoCache\Events\AutoCacheInvalidated` (`scope`: `record` \| `table` \| `lists`)

Optional buffering: skill `laravel-auto-cache-collector`.

## Serialization

File/Redis stores serialize Eloquent models. After deploys that change attributes/casts/relations: flush (`auto-cache:flush` / `autoCacheFlush`), bump `AUTO_CACHE_PREFIX`, or wait for TTL.

## Do / Don’t

**Do**

- Implement `AutoCacheable` and `use AutoCaches`.
- Put **table names** (not model class names) in `$cacheInvalidates`.
- Use `withoutCache()`, `autoCacheFlushLists()`, `autoCacheFlush()`, or `$cacheFlushListsOnSave` when a screen must see fresh lists after a one-row edit.
- Use `autoCacheForget` / `autoCacheFlush` / `auto-cache:flush` when writes bypass Eloquent.

**Don’t**

- Expect list/aggregate caches to clear on a single `save()` / `update()` unless `$cacheFlushListsOnSave` is enabled.
- Assume `$model->refresh()` or `replicate()` clears auto-cache.
- Call `Cache::flush()` as the normal invalidation path.
- Invent APIs (e.g. paginate caching) — not in v1.

## Quick opt-in

```php
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

class User extends Model implements AutoCacheable
{
    use AutoCaches;

    protected ?int $cacheTtl = null;
    protected bool $cacheMisses = false;
    /** @var list<string> */
    protected array $cacheInvalidates = [];
    /** @var list<string> */
    protected array $cacheSilentAttributes = [];
    protected bool $cacheFlushListsOnSave = false;
}
```

## Verify

Package repo: `composer check` (or `composer test` + `composer analyse`).  
Consumer app: feature-test a read path and a mutation that should invalidate; prefer Pest expectations (`laravel-auto-cache-pest`).
