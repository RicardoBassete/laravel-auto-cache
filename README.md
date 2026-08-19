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
    protected array $cacheInvalidates = []; // cascade tables on mutation
}
```

## Features

- Trait opt-in + custom `CachedBuilder`
- Caches `find` / `first` / `get` / `all` and aggregations (`count`, `exists`, `sum`, `pluck`, `value`)
- Keys: table+id for finds; SQL+bindings hash for filtered queries; table name for full scans
- Eager `with` included in the cache key
- Key registry for mass invalidation (any cache store; no tags required)
- Single-row mutations invalidate that record only; mass mutations flush the table registry
- Invalidation deferred with `DB::afterCommit()`
- `withoutCache()` escape hatch

## Install

```bash
composer require ricardobassete/laravel-auto-cache
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=auto-cache-config
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
