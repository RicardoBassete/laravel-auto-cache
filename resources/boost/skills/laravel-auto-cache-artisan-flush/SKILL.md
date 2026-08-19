---
name: laravel-auto-cache-artisan-flush
description: >-
  ACTIVATE when flushing ricardobassete/laravel-auto-cache from the CLI or deploy
  scripts via php artisan auto-cache:flush {table?}.
license: MIT
metadata:
  author: ricardobassete
---

# Artisan `auto-cache:flush`

```bash
php artisan auto-cache:flush users   # one table
php artisan auto-cache:flush         # all tables that have registered cache keys
```

Use after deploys that change model serialization, or when writes bypass Eloquent and you need an ops-level flush without `tinker`. Alternative logical flush: bump `AUTO_CACHE_PREFIX`.

## Notes

- Omitting `{table}` only flushes **tracked** tables (tables that registered at least one auto-cache key since the store was last emptied).
- Equivalent model API: `User::autoCacheFlush()` (also respects `$cacheInvalidates` cascade).
- List-only: `User::autoCacheFlushLists()` (not exposed as a separate artisan command).

## Checklist

- [ ] Deploy/runbook mentions flush or prefix bump when Eloquent model shape changes
- [ ] Prefer table argument in scripts when only one domain changed

## Related

- Overview: `laravel-auto-cache`
- Flush lists: `laravel-auto-cache-flush-lists`
- Manual: `autoCacheForget` / `autoCacheFlush` / `autoCacheFlushLists`
