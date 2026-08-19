---
name: laravel-auto-cache-flush-lists
description: >-
  ACTIVATE when configuring $cacheFlushListsOnSave or calling autoCacheFlushLists()
  for ricardobassete/laravel-auto-cache so single-row saves also clear list/query
  caches (or when manually flushing lists while keeping find keys).
license: MIT
metadata:
  author: ricardobassete
---

# Flush lists on save / `autoCacheFlushLists`

## Default vs opt-in

By default, a **single-row** mutation invalidates **only** that record’s find keys. List/`where`/`count` caches stay until TTL, mass mutation, or flush.

Set `$cacheFlushListsOnSave = true` when screens must see fresh lists after one-row edits.

## Template

```php
protected bool $cacheFlushListsOnSave = true;
```

On create/update/delete/restore/forceDelete (when invalidation runs): clears that record’s find keys **and** list/query/aggregation keys for the table. Other records’ find keys stay warm.

## Manual

```php
User::autoCacheFlushLists(); // list keys only; find keys stay
User::autoCacheFlush();      // everything for the table (+ cascade)
```

## Checklist

- [ ] Only enable on models where list staleness is a product bug
- [ ] Silent-only updates still skip all invalidation (including lists)
- [ ] Prefer `autoCacheFlushLists()` for one-off screens over enabling the flag globally

## Related

- Overview: `laravel-auto-cache`
- Silent attributes: `laravel-auto-cache-silent-attributes`
