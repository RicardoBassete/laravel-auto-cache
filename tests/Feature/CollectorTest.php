<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use RicardoBassete\AutoCache\Support\AutoCacheCollector;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
    config(['auto-cache.collector.enabled' => true]);
    app(AutoCacheCollector::class)->flush();
});

it('collects hits misses and invalidations when enabled', function (): void {
    $collector = app(AutoCacheCollector::class);

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    User::query()->find($user->id); // miss then put
    User::query()->find($user->id); // hit

    $user->update(['name' => 'Grace']);

    expect($collector->misses())->toBeGreaterThan(0)
        ->and($collector->hits())->toBeGreaterThan(0)
        ->and($collector->invalidations())->toBeGreaterThan(0)
        ->and($collector->entries())->not->toBeEmpty();
});

it('ignores events when collector is disabled', function (): void {
    config(['auto-cache.collector.enabled' => false]);
    $collector = app(AutoCacheCollector::class);
    $collector->flush();

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    User::query()->find($user->id);
    User::query()->find($user->id);

    expect($collector->entries())->toBeEmpty();
});
