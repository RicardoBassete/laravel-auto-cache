---
name: laravel-auto-cache-opt-in
description: >-
  Add ricardobassete/laravel-auto-cache opt-in to an Eloquent model (AutoCaches
  trait + AutoCacheable). Use when enabling auto cache on a model, wiring
  CachedBuilder, or converting a model to use AutoCaches.
---

# Opt-in a model to laravel-auto-cache

## Steps

1. Ensure the app requires `ricardobassete/laravel-auto-cache`.
2. On the Eloquent model:
   - `implements AutoCacheable`
   - `use AutoCaches`
   - Declare the three optional properties (even if defaults) for clarity.
3. Do **not** extend a custom base model unless the app already has one; trait opt-in is the supported path.
4. If the model already defines `newEloquentBuilder()`, stop and reconcile — `AutoCaches` provides one returning `CachedBuilder`.

## Template

```php
use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

class Order extends Model implements AutoCacheable
{
    use AutoCaches;

    protected ?int $cacheTtl = null;        // null => config auto-cache.ttl (3600)
    protected bool $cacheMisses = false;  // default: do not cache null/empty/false
    /** @var list<string> */
    protected array $cacheInvalidates = []; // table names to flush on mutation
}
```

## Checklist

- [ ] `AutoCacheable` + `AutoCaches` present
- [ ] No conflicting `newEloquentBuilder()`
- [ ] Soft deletes OK as-is (`restore` / `forceDelete` already invalidate)
- [ ] Related models that embed this model via `with()` considered for cascade on the **child** (see `laravel-auto-cache-cascade`)
- [ ] Test: warm `Model::query()->find($id)`, mutate row outside Eloquent or via another process simulation, assert cached value until model mutation invalidates

## Related

- Cascade: `laravel-auto-cache-cascade`
- TTL / misses: `laravel-auto-cache-ttl-misses`
- Bypass: `laravel-auto-cache-bypass`
