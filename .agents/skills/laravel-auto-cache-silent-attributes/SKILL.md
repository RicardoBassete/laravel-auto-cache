---
name: laravel-auto-cache-silent-attributes
description: >-
  ACTIVATE when configuring $cacheSilentAttributes for ricardobassete/laravel-auto-cache
  so updates that only touch listed columns skip cache invalidation (e.g. counters,
  last_seen_at, cosmetic fields).
license: MIT
metadata:
  author: ricardobassete
---

# Silent attributes (`$cacheSilentAttributes`)

## Rule

On **`updated`** only: if **every** changed attribute is listed in `$cacheSilentAttributes`, skip auto-cache invalidation for that update.

Create / delete / restore / forceDelete always invalidate. Mass query mutations still flush.

## Template

```php
/** @var list<string> */
protected array $cacheSilentAttributes = [
    'last_seen_at',
    'login_count',
];
```

## When to use

- High-churn columns that are not part of cached payloads you care about.
- Avoid listing columns that appear in cached API resources / screens.

## Checklist

- [ ] List is attribute names as stored on the model (not DB expressions)
- [ ] Mixed updates (silent + non-silent) still invalidate
- [ ] Test covers silent-only vs non-silent change

## Related

- Overview: `laravel-auto-cache`
- Opt-in: `laravel-auto-cache-opt-in`
