## laravel-auto-cache

Opt-in Eloquent query caching via `AutoCaches` + `AutoCacheable` (`ricardobassete/laravel-auto-cache`).

### Rules

- Opt-in per model only — never enable globally.
- Implement `RicardoBassete\AutoCache\Contracts\AutoCacheable` and `use RicardoBassete\AutoCache\Concerns\AutoCaches`.
- Single-row mutations invalidate that record’s find cache only; mass query mutations flush the table registry.
- Invalidation is deferred with `DB::afterCommit()`.
- `$cacheInvalidates` lists **table names** to cascade-flush on mutation.

### Skills (activate as needed)

- IMPORTANT: Activate `laravel-auto-cache` for general package usage.
- IMPORTANT: Activate `laravel-auto-cache-opt-in` when adding the trait to a model.
- IMPORTANT: Activate `laravel-auto-cache-cascade` when configuring `$cacheInvalidates`.
- IMPORTANT: Activate `laravel-auto-cache-ttl-misses` when setting `$cacheTtl` or `$cacheMisses`.
- IMPORTANT: Activate `laravel-auto-cache-bypass` when using `withoutCache()` / `withCache()`.
