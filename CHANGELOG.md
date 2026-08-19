# Changelog

All notable changes to this project are documented in this file.

## [0.1.0] — 2026-08-18

### Added

- Opt-in `AutoCaches` trait + `AutoCacheable` contract and `CachedBuilder`
- Query caching for `find` / `findMany` / `findOrFail` / `first` / `firstOrFail` / `get` / `all` and aggregations
- Key registry invalidation (single-record vs mass), `$cacheInvalidates` cascade, `afterCommit`
- `withoutCache()` / `withCache()`, `$cacheTtl`, `$cacheMisses`
- Manual APIs: `autoCacheForget()`, `autoCacheFlush()`, `autoCacheForgetSelf()`
- Laravel Boost skills under `resources/boost/skills/` + `resources/boost/guidelines/core.blade.php`
- Pest + Testbench suite (coverage gate 80%)

### Notes

- Single-row mutations intentionally leave list/aggregate caches stale until TTL, mass flush, or cascade — see README.
