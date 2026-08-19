---
name: laravel-auto-cache-bypass
description: >-
  ACTIVATE when bypassing ricardobassete/laravel-auto-cache via
  withoutCache()/withCache(), forcing a DB read, seeding stale-cache tests, or
  debugging cached Eloquent results.
license: MIT
metadata:
  author: ricardobassete
---

# Bypass cache (`withoutCache`)

## API

```php
// Static — new query with caching off
User::withoutCache()->find($id);
User::withoutCache()->where('active', true)->get();

// Builder chain
User::query()->withoutCache()->count();

// Re-enable on the same builder instance
User::withoutCache()->withCache()->find($id);
```

## Semantics

- Affects **reads** (no cache hit/put) on that builder.
- **Mutations still invalidate** as usual (`update` / `delete` / model events). Turning off cache does **not** skip invalidation.
- To change DB data **without** invalidating (tests only), use `DB::table(...)` or a path that never fires Eloquent model events / `CachedBuilder` mass invalidation.

## When to use

- Admin/export paths that must see committed truth immediately after another process wrote data.
- Feature tests asserting bypass behavior.
- Avoid as a substitute for `$cacheFlushListsOnSave`, `autoCacheFlushLists()`, correct `$cacheInvalidates`, or mass invalidation.

## Checklist

- [ ] Bypass scoped to the smallest query
- [ ] Not used to “fix” stale relation caches — fix cascade instead (`laravel-auto-cache-cascade`)
- [ ] Not used to “fix” stale lists after one-row edits — use `laravel-auto-cache-flush-lists`
- [ ] Tests that need stale cache use `DB::table` updates, not `withoutCache()->update()`

## Related

- Overview: `laravel-auto-cache`
- Cascade: `laravel-auto-cache-cascade`
- Flush lists: `laravel-auto-cache-flush-lists`
- Pest: `laravel-auto-cache-pest`
