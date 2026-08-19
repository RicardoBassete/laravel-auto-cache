---
name: laravel-auto-cache
description: >-
  Integrate and operate ricardobassete/laravel-auto-cache on Eloquent models.
  Use when adding AutoCaches, AutoCacheable, query caching, cache invalidation,
  $cacheInvalidates, $cacheTtl, $cacheMisses, withoutCache, or CachedBuilder.
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
| General behavior / mental model | this skill |

Read the focused skill before editing; do not invent APIs beyond what those skills document.

## Install (consumer app)

```bash
composer require ricardobassete/laravel-auto-cache
php artisan vendor:publish --tag=auto-cache-config   # optional
```

Config keys (`config/auto-cache.php`): `store` (null = app default), `ttl` (3600), `prefix`, `lock_seconds`.

## Mental model

- **Opt-in only** via trait + `AutoCacheable` on the model.
- Reads cached: `find` / `first` / `get` / `all`, plus `count` / `exists` / `sum` / `pluck` / `value`.
- Mutations do not cache results; they invalidate.
- **Single-row** mutation → invalidate that record’s find keys only (lists/aggregates may stay stale until TTL or mass flush).
- **Mass** mutation (`where(...)->update/delete/insert/upsert`) → flush all registered keys for the table (+ `$cacheInvalidates`).
- Invalidation runs **`DB::afterCommit()`** (immediate if not in a transaction).
- Eager `with` is part of the cache key; relation queries during eager load are **not** cached separately.
- Any `Cache::store()` works; no tags required (key registry).

## Do / Don’t

**Do**

- Implement `AutoCacheable` and `use AutoCaches`.
- Put table names (not model class names) in `$cacheInvalidates`.
- Use `withoutCache()` when you must read/write through to the DB without hits.

**Don’t**

- Expect list/aggregate caches to clear on a single `save()` / `update()` on one model.
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
