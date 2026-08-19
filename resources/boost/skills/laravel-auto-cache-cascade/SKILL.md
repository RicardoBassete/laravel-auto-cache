---
name: laravel-auto-cache-cascade
description: >-
  ACTIVATE when configuring $cacheInvalidates for ricardobassete/laravel-auto-cache
  cascade table flushing — e.g. Post updates must invalidate users caches used
  with with('posts').
license: MIT
metadata:
  author: ricardobassete
---

# Cascade invalidation (`$cacheInvalidates`)

## Rule

On mutation of model **A**, after invalidating A’s own cache (record or table), also flush the **full key registry** for each table listed in A’s `$cacheInvalidates`.

Values are **table names** (e.g. `users`), not FQCNs.

## When to add an entry

Add table `T` to model A’s `$cacheInvalidates` when:

- Callers cache queries on `T` that embed A’s data (typical: `T::with('aRelation')->...`), **or**
- Stale A would make cached `T` payloads wrong before TTL.

Skip when only A’s own find/list caches matter.

## Steps

1. Identify the model that **mutates** (usually the child / belonging side).
2. Identify the **parent/other** table whose caches must die.
3. Set on the mutating model:

```php
/** @var list<string> */
protected array $cacheInvalidates = ['users']; // table name(s)
```

4. Keep the list minimal — each entry flushes **all** registered keys for that table (find + queries + aggregates).

## Example

```php
// posts mutation should refresh User::with('posts') caches
class Post extends Model implements AutoCacheable
{
    use AutoCaches;

    /** @var list<string> */
    protected array $cacheInvalidates = ['users'];
}
```

## Checklist

- [ ] Entries are table names matching `Model::getTable()` of the target
- [ ] Mutating model (not only the parent) owns `$cacheInvalidates`
- [ ] Test: cache `Parent::with('child')->find($id)`, update child, assert parent re-read sees new child data
- [ ] Aware that cascade is a **table flush**, not per-id on the target table

## Related

- Opt-in: `laravel-auto-cache-opt-in`
- Overview: `laravel-auto-cache`
