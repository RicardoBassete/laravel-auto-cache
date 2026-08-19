---
name: laravel-auto-cache
description: >-
  ACTIVATE when working with ricardobassete/laravel-auto-cache, AutoCaches,
  AutoCacheable, CachedBuilder, Eloquent query caching, $cacheInvalidates,
  $cacheTtl, $cacheMisses, or withoutCache. Use for opt-in model caching,
  invalidation rules, cascade tables, TTL/miss config, and cache bypass.
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

Config keys (`config/auto-cache.php`): `store` (null = app default), `ttl` (3600), `prefix`, `lock_seconds`.

After requiring the package, run `php artisan boost:update` (or `boost:install`) so Boost installs these skills into the agent skills path.

## Mental model

- **Opt-in only** via trait + `AutoCacheable` on the model.
- Reads cached: `find` / `findMany` / `findOrFail` / `first` / `firstOrFail` / `get` / `all`, plus `count` / `exists` / `sum` / `pluck` / `value`.
- Mutations do not cache results; they invalidate.
- **Single-row** mutation → invalidate that record’s find keys only. **List/`where`/`count` caches stay stale** until TTL, mass mutation, `autoCacheFlush()`, or cascade — this is intentional.
- **Mass** mutation (`where(...)->update/delete/insert/upsert`) → flush all registered keys for the table (+ `$cacheInvalidates`).
- Invalidation runs **`DB::afterCommit()`** (immediate if not in a transaction).
- Eager `with` is part of the cache key; relation queries during eager load are **not** cached separately.
- Any `Cache::store()` works; no tags required (key registry).
- Manual: `Model::autoCacheForget($id)`, `Model::autoCacheFlush()`, `Model::autoCacheFlushLists()`, `$model->autoCacheForgetSelf()`.
- Opt-in `$cacheFlushListsOnSave`: single-row mutations also clear list/query keys (other finds stay).

## Serialization

File/Redis stores serialize Eloquent models. After deploys that change attributes/casts/relations, flush or wait for TTL — stale payloads can break unserialize.

## Do / Don’t

**Do**

- Implement `AutoCacheable` and `use AutoCaches`.
- Put table names (not model class names) in `$cacheInvalidates`.
- Use `withoutCache()` or `autoCacheFlush()` when a screen must see fresh lists after a one-row edit.
- Use `autoCacheForget` / `autoCacheFlush` when writes bypass Eloquent.

**Don’t**

- Expect list/aggregate caches to clear on a single `save()` / `update()` on one model.
- Assume `$model->refresh()` or `replicate()` clears auto-cache — they only affect the in-memory instance / a new unsaved copy.
- Cache across requests expecting identity of Eloquent instances (serialize-safe stores differ from array).
- Call `Cache::flush()` as the normal invalidation path.

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
}
```

## Verify

After changes that touch caching behavior in the package repo: `composer check` (or `composer test` + `composer analyse`).  
In a consumer app: feature-test the model’s read path and a mutation that should invalidate.
