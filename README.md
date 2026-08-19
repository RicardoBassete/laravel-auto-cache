# laravel-auto-cache

Opt-in Eloquent query caching for Laravel 11+.

```php
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

class User extends Model implements AutoCacheable
{
    use AutoCaches;

    protected ?int $cacheTtl = null;        // null => config (3600s)
    protected bool $cacheMisses = false;    // default: do not cache misses
    /** @var list<string> */
    protected array $cacheInvalidates = []; // cascade tables on mutation
}
```

## Features

- Trait opt-in + custom `CachedBuilder`
- Caches `find` / `findMany` / `findOrFail` / `first` / `firstOrFail` / `get` / `all` and aggregations (`count`, `exists`, `sum`, `pluck`, `value`)
- Keys: table+id for finds; SQL+bindings hash for filtered queries; table name for full scans
- Eager `with` included in the cache key
- Key registry for mass invalidation (any cache store; no tags required)
- Single-row mutations invalidate that record only; mass mutations flush the table registry
- Invalidation deferred with `DB::afterCommit()`
- `withoutCache()` escape hatch
- Manual invalidation: `Model::autoCacheForget($id)`, `Model::autoCacheFlush()`, `$model->autoCacheForgetSelf()`
- Debug helpers: `Model::autoCacheKeys()`, `Model::autoCacheRemember($id, fn)`

## Important: intentional cache staleness

A **single-row** Eloquent mutation (`$user->update(...)`, `save`, soft delete, restore) invalidates **only that record’s find keys**.

It does **not** clear:

- `User::where(...)->get()` / `first()` list caches
- Aggregations (`count`, `sum`, …)

Those stay until TTL expires, a **mass** mutation runs (`User::query()->where(...)->update()`), `autoCacheFlush()`, or a cascade via `$cacheInvalidates`.

This matches the package domain rules. If a screen must see fresh lists after one-row edits, call `User::autoCacheFlush()`, use `withoutCache()` on that read, or redesign the write as a mass invalidation path.

## Serialization warning

File/Redis (and other serializing stores) persist Eloquent models (and loaded relations). After deploys that change attributes, casts, or relation shapes, stale payloads can fail to unserialize or look wrong until TTL/invalidation. Prefer short TTLs on volatile models, or flush after deploy when the schema of cached models changes.

## Install

### Packagist (when published)

```bash
composer require ricardobassete/laravel-auto-cache
```

### GitHub (VCS)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/RicardoBassete/laravel-auto-cache.git"
    }
  ],
  "require": {
    "ricardobassete/laravel-auto-cache": "^0.1"
  }
}
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=auto-cache-config
```

### Manual invalidation

```php
User::autoCacheForget($user->id);   // find keys for id + cascade tables
User::autoCacheFlush();             // all keys for users (+ cascade)
$user->autoCacheForgetSelf();
```

Use when writes bypass Eloquent (`DB::table`, external jobs).

## Agent skills & Laravel Boost

Boost discovers package skills from [`resources/boost/skills/`](resources/boost/skills/) and guidelines from [`resources/boost/guidelines/core.blade.php`](resources/boost/guidelines/core.blade.php). After `composer require`, consumers should run:

```bash
php artisan boost:update
# or: php artisan boost:install
```

and select this package’s skills/guidelines when prompted. Boost copies skills into the agent path (e.g. `.agents/skills`, `.cursor/skills`, `.claude/skills`).

| Skill | Use when |
| --- | --- |
| `laravel-auto-cache` | General package usage |
| `laravel-auto-cache-opt-in` | Adding the trait to a model |
| `laravel-auto-cache-cascade` | `$cacheInvalidates` tables |
| `laravel-auto-cache-ttl-misses` | `$cacheTtl` / `$cacheMisses` |
| `laravel-auto-cache-bypass` | `withoutCache()` |

Canonical copies for Boost live under `resources/boost/skills/`. Matching files under [`.agents/skills/`](.agents/skills/) stay in sync for agents opened on this package repo.

## Development

Requires PHP with **pcov** or **Xdebug** for coverage (`composer test` enforces ≥ 80%).

```bash
composer install
composer check   # pint + phpstan + rector dry-run + pest (coverage ≥ 80%)
```

Without a coverage driver, run `./vendor/bin/pest` directly.

## License

MIT
