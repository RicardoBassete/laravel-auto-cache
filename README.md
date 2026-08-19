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

Optional: `$cacheSilentAttributes` — updates that only change those columns skip invalidation (see skill `laravel-auto-cache-silent-attributes`).

Optional: `$cacheFlushListsOnSave = true` — single-row saves also clear list/query caches (see skill `laravel-auto-cache-flush-lists`).

## Features

- Trait opt-in + custom `CachedBuilder`
- Caches `find` / `findMany` / `findOrFail` / `first` / `firstOrFail` / `get` / `all` and aggregations (`count`, `exists`, `sum`, `pluck`, `value`)
- Keys: table+id for finds; SQL+bindings hash for filtered queries; table name for full scans
- Eager `with` included in the cache key
- Key registry for mass invalidation (any cache store; no tags required)
- Single-row mutations invalidate that record only; mass mutations flush the table registry
- Invalidation deferred with `DB::afterCommit()`
- `withoutCache()` escape hatch
- Manual invalidation: `Model::autoCacheForget($id)`, `Model::autoCacheFlush()`, `Model::autoCacheFlushLists()`, `$model->autoCacheForgetSelf()`
- Opt-in `$cacheFlushListsOnSave` to clear list/query caches on single-row mutations
- Debug helpers: `Model::autoCacheKeys()`, `Model::autoCacheRemember($id, fn)`
- Observability events: `AutoCacheHit`, `AutoCacheMiss`, `AutoCacheInvalidated`
- Optional request collector (+ Telescope / Debugbar bridges)
- Artisan: `php artisan auto-cache:flush {table?}`
- Pest expectations (optional): `toHaveCachedFind` / `toMissCachedFind`

## Important: intentional cache staleness

A **single-row** Eloquent mutation (`$user->update(...)`, `save`, soft delete, restore) invalidates **only that record’s find keys**.

It does **not** clear:

- `User::where(...)->get()` / `first()` list caches
- Aggregations (`count`, `sum`, …)

Those stay until TTL expires, a **mass** mutation runs (`User::query()->where(...)->update()`), `autoCacheFlush()`, `autoCacheFlushLists()`, cascade via `$cacheInvalidates`, or the model opts into `$cacheFlushListsOnSave = true`.

This matches the package domain rules. If a screen must see fresh lists after one-row edits, call `User::autoCacheFlush()`, use `withoutCache()` on that read, or redesign the write as a mass invalidation path.

## `refresh()` and `replicate()` are not invalidation

- `$model->refresh()` reloads **that in-memory instance** from the database. It does **not** forget auto-cache keys. Other requests (and later `Model::find($id)` in the same process) can still receive the previous cached payload until invalidation/TTL.
- `$model->replicate()` copies attributes into a **new** unsaved instance. It does not read or write the auto-cache.

After external writes, use `autoCacheForget($id)`, `autoCacheFlush()`, model mutations that fire events, or `withoutCache()->find($id)`.

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
User::autoCacheFlushLists();        // list/query keys only (find keys stay)
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
| `laravel-auto-cache-silent-attributes` | `$cacheSilentAttributes` |
| `laravel-auto-cache-flush-lists` | `$cacheFlushListsOnSave` / `autoCacheFlushLists()` |
| `laravel-auto-cache-pest` | Pest `toHaveCachedFind` / `toMissCachedFind` |
| `laravel-auto-cache-collector` | Request collector / Telescope / Debugbar |
| `laravel-auto-cache-artisan-flush` | `php artisan auto-cache:flush` |

Canonical copies for Boost live under `resources/boost/skills/`. Matching files under [`.agents/skills/`](.agents/skills/) stay in sync for agents opened on this package repo.

## Pest expectations

With `pestphp/pest` installed, the package registers a Bootable Pest plugin. In consumer (or package) tests:

```php
User::query()->find($user->id);

expect(User::class)->toHaveCachedFind($user->id);
expect(User::class)->toMissCachedFind($user->id);
expect(User::class)->toHaveCachedFind($user->id, ['posts']); // with eager loads
```

## Request collector

```env
AUTO_CACHE_COLLECTOR=true
```

Buffer hit/miss/invalidation on `AutoCacheCollector` for the current request. With `laravel/telescope` or `barryvdh/laravel-debugbar` installed, optional bridges feed Telescope’s Cache watcher and a Debugbar `auto-cache` panel (see skill `laravel-auto-cache-collector`).

## Artisan flush

```bash
php artisan auto-cache:flush users
php artisan auto-cache:flush          # all tracked tables
```

## Development

Requires PHP with **pcov** or **Xdebug** for coverage (`composer test` enforces ≥ 80%).

```bash
composer install
composer check   # pint + phpstan + rector dry-run + pest (coverage ≥ 80%)
```

Without a coverage driver, run `./vendor/bin/pest` directly.

## License

MIT
