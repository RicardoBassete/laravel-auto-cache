<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RicardoBassete\AutoCache\Events\AutoCacheHit;
use RicardoBassete\AutoCache\Events\AutoCacheInvalidated;
use RicardoBassete\AutoCache\Events\AutoCacheMiss;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('dispatches hit and miss events for finds', function (): void {
    Event::fake([AutoCacheHit::class, AutoCacheMiss::class]);

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    User::query()->find($user->id);
    Event::assertDispatched(AutoCacheMiss::class);
    Event::assertNotDispatched(AutoCacheHit::class);

    Event::fake([AutoCacheHit::class, AutoCacheMiss::class]);
    User::query()->find($user->id);
    Event::assertDispatched(AutoCacheHit::class);
});

it('dispatches invalidated events on model update', function (): void {
    Event::fake([AutoCacheInvalidated::class]);

    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    User::query()->find($user->id);

    $user->update(['name' => 'Grace']);

    Event::assertDispatched(
        AutoCacheInvalidated::class,
        fn (AutoCacheInvalidated $event): bool => $event->table === 'users'
            && $event->scope === 'record'
            && $event->recordId === $user->id,
    );
});
