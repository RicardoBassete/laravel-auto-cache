## laravel-auto-cache

Opt-in Eloquent query caching via `AutoCaches` + `AutoCacheable` (`ricardobassete/laravel-auto-cache`).

### Rules

- Opt-in per model only — never enable globally.
- Implement `RicardoBassete\AutoCache\Contracts\AutoCacheable` and `use RicardoBassete\AutoCache\Concerns\AutoCaches`.
- Single-row mutations invalidate that record’s find cache only; **list/aggregate caches stay stale** until TTL, mass flush, cascade, or `autoCacheFlush()`.
- Invalidation is deferred with `DB::afterCommit()`.
- `$cacheInvalidates` lists **table names** to cascade-flush on mutation.
- Manual APIs: `Model::autoCacheForget($id)`, `Model::autoCacheFlush()`, `$model->autoCacheForgetSelf()`.
- `$model->refresh()` / `replicate()` do **not** invalidate auto-cache keys.
- Serializing stores (file/redis) persist Eloquent models — flush after breaking model shape changes.

### Skills (activate as needed)

- IMPORTANT: Activate `laravel-auto-cache` for general package usage.
- IMPORTANT: Activate `laravel-auto-cache-opt-in` when adding the trait to a model.
- IMPORTANT: Activate `laravel-auto-cache-cascade` when configuring `$cacheInvalidates`.
- IMPORTANT: Activate `laravel-auto-cache-ttl-misses` when setting `$cacheTtl` or `$cacheMisses`.
- IMPORTANT: Activate `laravel-auto-cache-bypass` when using `withoutCache()` / `withCache()`.
- IMPORTANT: Activate `laravel-auto-cache-silent-attributes` when configuring `$cacheSilentAttributes`.
- IMPORTANT: Activate `laravel-auto-cache-pest` when writing Pest assertions for auto-cache hits/misses.
