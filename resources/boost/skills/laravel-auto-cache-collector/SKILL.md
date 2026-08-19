---
name: laravel-auto-cache-collector
description: >-
  ACTIVATE when enabling the AutoCache request collector, Telescope Cache entries,
  or Barryvdh Debugbar panel for ricardobassete/laravel-auto-cache hit/miss/invalidation
  observability.
license: MIT
metadata:
  author: ricardobassete
---

# Request collector / Telescope / Debugbar

## Enable

```env
AUTO_CACHE_COLLECTOR=true
# optional bridges (default true when collector is on and packages exist):
AUTO_CACHE_TELESCOPE=true
AUTO_CACHE_DEBUGBAR=true
```

Or `config/auto-cache.php` → `collector.enabled`.

## In-request API

```php
use RicardoBassete\AutoCache\Support\AutoCacheCollector;

$collector = app(AutoCacheCollector::class);
$collector->hits();
$collector->misses();
$collector->invalidations();
$collector->entries();
```

## Integrations

- **Telescope** (optional): when `laravel/telescope` is installed and recording, events are pushed via `Telescope::recordCache` (type hit/miss/forget, `value.source = auto-cache`).
- **Debugbar** (optional): when `barryvdh/laravel-debugbar` is installed, an `auto-cache` panel is registered automatically.

## Related

- Overview: `laravel-auto-cache`
- Events are always dispatched regardless of collector (`AutoCacheHit` / `Miss` / `Invalidated`)
