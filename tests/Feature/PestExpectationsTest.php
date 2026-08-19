<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Pest\Plugin;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('asserts a cached find with toHaveCachedFind', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(User::class)->toMissCachedFind($user->id);

    User::query()->find($user->id);

    expect(User::class)->toHaveCachedFind($user->id);

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(User::class)->toHaveCachedFind($user->id)
        ->and(User::query()->find($user->id)?->name)->toBe('Ada');
});

it('asserts miss after forget with toMissCachedFind', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    User::query()->find($user->id);
    expect(User::class)->toHaveCachedFind($user->id);

    User::autoCacheForget($user->id);

    expect(User::class)->toMissCachedFind($user->id);
});

it('boots the Pest plugin idempotently', function (): void {
    $plugin = new Plugin;
    $plugin->boot();

    expect(User::class)->toMissCachedFind(999_999);
});
