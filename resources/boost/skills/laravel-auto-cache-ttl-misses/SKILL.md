---
name: laravel-auto-cache-ttl-misses
description: >-
  ACTIVATE when configuring $cacheTtl or $cacheMisses for
  ricardobassete/laravel-auto-cache, changing cache lifetime, enabling
  null/empty miss caching, or aligning TTL with config/auto-cache.php.
license: MIT
metadata:
  author: ricardobassete
---

# TTL and miss caching

## Defaults

| Setting | Default |
| --- | --- |
| `$cacheTtl` | `null` → `config('auto-cache.ttl')` → **3600** |
| `$cacheMisses` | `false` → do **not** store `null` / empty collection / `false` |

## TTL

```php
protected ?int $cacheTtl = 600; // seconds; null = config default
```

- Prefer config for app-wide policy; override on hot or cold models only.
- Registry entries use the same TTL machinery as values (orphans die by expiry if lock/registry races).

## Miss caching

```php
protected bool $cacheMisses = true;
```

Enable when repeated `find` of missing ids or empty filters hammer the DB.

**Must** invalidate on later create of that id (single-record invalidation on `created` clears that id’s record keys). List/aggregate misses still follow single vs mass rules.

## Steps

1. Decide app default in `config/auto-cache.php` (`ttl`).
2. Set `$cacheTtl` only when this model needs a different lifetime.
3. Set `$cacheMisses = true` only with a clear stampede/miss pattern.
4. Add/adjust a test for miss behavior if enabling `$cacheMisses`.

## Checklist

- [ ] TTL unit is seconds
- [ ] `$cacheMisses` default left `false` unless justified
- [ ] Create-after-miss path covered when misses are cached

## Related

- Opt-in: `laravel-auto-cache-opt-in`
- Overview: `laravel-auto-cache`
