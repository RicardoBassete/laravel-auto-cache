<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache;

use Illuminate\Support\ServiceProvider;

final class AutoCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/auto-cache.php', 'auto-cache');

        $this->app->singleton(CacheManager::class, fn (): CacheManager => new CacheManager);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/auto-cache.php' => config_path('auto-cache.php'),
            ], 'auto-cache-config');
        }
    }
}
