<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache;

use Illuminate\Support\ServiceProvider;
use RicardoBassete\AutoCache\Support\AutoCacheCollector;
use RicardoBassete\AutoCache\Support\AutoCacheObservability;
use RicardoBassete\AutoCache\Support\DebugbarBridge;

final class AutoCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auto-cache.php', 'auto-cache');

        $this->app->singleton(CacheManager::class, fn (): CacheManager => new CacheManager);
        $this->app->singleton(AutoCacheCollector::class, fn (): AutoCacheCollector => new AutoCacheCollector);
    }

    public function boot(): void
    {
        AutoCacheObservability::register($this->app->make(AutoCacheCollector::class));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/auto-cache.php' => config_path('auto-cache.php'),
            ], 'auto-cache-config');
        }

        $this->app->booted(function (): void {
            DebugbarBridge::register($this->app->make(AutoCacheCollector::class));
        });
    }
}
