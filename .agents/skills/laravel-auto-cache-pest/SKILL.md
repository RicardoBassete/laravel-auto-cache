---
name: laravel-auto-cache-pest
description: >-
  ACTIVATE when writing Pest tests for apps that use ricardobassete/laravel-auto-cache.
  Use package expectations toHaveCachedFind / toMissCachedFind instead of poking CacheManager
  keys manually.
license: MIT
metadata:
  author: ricardobassete
---

# Pest expectations

Requires `pestphp/pest`. The package registers a Pest plugin via `extra.pest.plugins`.

## Usage

```php
use App\Models\User;

User::query()->find($id);

expect(User::class)->toHaveCachedFind($id);
expect(User::class)->toMissCachedFind($id);

// Eager-load variants use a different key:
User::query()->with('posts')->find($id);
expect(User::class)->toHaveCachedFind($id, ['posts']);
```

## Notes

- Value must be a **Model class-string**, not an instance.
- These check the **record find key** (table + id + with hash), not list/query keys.

## Related

- Overview: `laravel-auto-cache`
- Bypass: `laravel-auto-cache-bypass`
