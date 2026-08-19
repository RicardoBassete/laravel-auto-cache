---
name: laravel-auto-cache-opt-in
description: >-
  ACTIVATE when adding ricardobassete/laravel-auto-cache to an Eloquent model
  (AutoCaches + AutoCacheable), enabling CachedBuilder, or converting a model
  to opt-in query caching.
license: MIT
metadata:
  author: ricardobassete
---

# Opt-in a model to laravel-auto-cache

## Steps

1. Ensure the app requires `ricardobassete/laravel-auto-cache`.
2. On the Eloquent model:
   - `implements AutoCacheable`
   - `use AutoCaches`
   - Declare optional properties you need (defaults are fine if omitted).
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

    protected ?int $cacheTtl = null;                 // null => config auto-cache.ttl (3600)
    protected bool $cacheMisses = false;             // default: do not cache null/empty/false
    /** @var list<string> */
    protected array $cacheInvalidates = [];          // table names to flush on mutation
    /** @var list<string> */
    protected array $cacheSilentAttributes = [];     // cosmetic cols — skip invalidation on updated
    protected bool $cacheFlushListsOnSave = false;   // also clear list/query keys on single-row save
}
```

Only set non-defaults when needed; see focused skills for each property.

## Checklist

- [ ] `AutoCacheable` + `AutoCaches` present
- [ ] No conflicting `newEloquentBuilder()`
- [ ] Soft deletes OK as-is (`restore` / `forceDelete` already invalidate)
- [ ] Related models that embed this model via `with()` considered for cascade on the **child** (see `laravel-auto-cache-cascade`)
- [ ] Decide whether list staleness after one-row edits is acceptable (see `laravel-auto-cache-flush-lists`)
- [ ] Test: warm `Model::query()->find($id)`, mutate via `DB::table` (no Eloquent events), assert cached value until model mutation invalidates — or use `toHaveCachedFind` / `toMissCachedFind`

## Related

- Cascade: `laravel-auto-cache-cascade`
- TTL / misses: `laravel-auto-cache-ttl-misses`
- Silent attributes: `laravel-auto-cache-silent-attributes`
- Flush lists: `laravel-auto-cache-flush-lists`
- Bypass: `laravel-auto-cache-bypass`
- Pest: `laravel-auto-cache-pest`
- Overview: `laravel-auto-cache`
