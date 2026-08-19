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

Use after deploys that change model serialization, or when writes bypass Eloquent and you need an ops-level flush without `tinker`.

## Notes

- Omitting `{table}` only flushes **tracked** tables (tables that have registered at least one auto-cache key since the last store flush).
- Equivalent model API: `User::autoCacheFlush()`.

## Related

- Overview: `laravel-auto-cache`
- Manual invalidation: `autoCacheForget` / `autoCacheFlush` / `autoCacheFlushLists`
