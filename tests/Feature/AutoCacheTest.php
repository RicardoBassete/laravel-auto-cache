<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Tests\Models\CacheMissUser;
use RicardoBassete\AutoCache\Tests\Models\Post;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('caches find by id and returns cached model on second call', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $first = User::query()->find($user->id);
    expect($first)->not->toBeNull()
        ->and($first?->name)->toBe('Ada');

    // Bypass Eloquent events/invalidation to prove the find cache sticks.
    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    $second = User::query()->find($user->id);
    expect($second?->name)->toBe('Ada');
});

it('does not cache find misses by default', function (): void {
    $manager = app(CacheManager::class);
    $key = $manager->recordKey('users', 999);

    expect(User::query()->find(999))->toBeNull()
        ->and($manager->has($key))->toBeFalse();
});

it('caches find misses when cacheMisses is true', function (): void {
    $manager = app(CacheManager::class);
    $key = $manager->recordKey('users', 999);

    expect(CacheMissUser::query()->find(999))->toBeNull()
        ->and($manager->has($key))->toBeTrue()
        ->and(CacheMissUser::query()->find(999))->toBeNull();
});

it('caches filtered get queries by sql hash', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'active' => true]);
    User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'active' => false]);

    $first = User::query()->where('active', true)->get();
    expect($first)->toHaveCount(1);

    DB::table('users')->where('active', true)->update(['name' => 'Changed']);

    $second = User::query()->where('active', true)->get();
    expect($second->first()?->name)->toBe('A');
});

it('caches aggregations', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com']);
    User::query()->create(['name' => 'B', 'email' => 'b@example.com']);

    expect(User::query()->count())->toBe(2)
        ->and(User::query()->pluck('email'))->toHaveCount(2);

    // Single-row create invalidates only that record, not aggregation keys.
    User::query()->create(['name' => 'C', 'email' => 'c@example.com']);

    expect(User::query()->count())->toBe(2)
        ->and(User::query()->exists())->toBeTrue()
        ->and(User::query()->pluck('email'))->toHaveCount(2);
});

it('bypasses cache with withoutCache', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    User::query()->find($user->id);
    DB::table('users')->where('id', $user->id)->update(['name' => 'Grace']);

    expect(User::withoutCache()->find($user->id)?->name)->toBe('Grace');
});

it('invalidates only the record cache on single update', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);
    User::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'active' => true]);

    $cachedFind = User::query()->find($user->id);
    $cachedList = User::query()->where('active', true)->get();
    expect($cachedFind?->name)->toBe('Ada')
        ->and($cachedList)->toHaveCount(2);

    $user->update(['name' => 'Updated']);

    expect(User::query()->find($user->id)?->name)->toBe('Updated')
        ->and(User::query()->where('active', true)->get())->toHaveCount(2)
        ->and(User::query()->where('active', true)->get()->firstWhere('id', $user->id)?->name)->toBe('Ada');
});

it('invalidates all table caches on mass update', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'active' => true]);
    User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'active' => true]);

    expect(User::query()->where('active', true)->count())->toBe(2);

    User::query()->where('active', true)->update(['active' => false]);

    expect(User::query()->where('active', true)->count())->toBe(0);
});

it('includes eager loads in the cache key', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'First']);

    $withPosts = User::query()->with('posts')->find($user->id);
    expect($withPosts?->relationLoaded('posts'))->toBeTrue()
        ->and($withPosts?->posts)->toHaveCount(1);

    DB::table('posts')->insert([
        'user_id' => $user->id,
        'title' => 'Second',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cachedWith = User::query()->with('posts')->find($user->id);
    expect($cachedWith?->posts)->toHaveCount(1);

    $without = User::query()->find($user->id);
    expect($without?->relationLoaded('posts'))->toBeFalse();
});

it('cascades invalidation via cacheInvalidates', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $post = Post::query()->create(['user_id' => $user->id, 'title' => 'First']);

    $cached = User::query()->with('posts')->find($user->id);
    expect($cached?->posts)->toHaveCount(1);

    $post->update(['title' => 'Changed']);

    $fresh = User::query()->with('posts')->find($user->id);
    expect($fresh?->posts->first()?->title)->toBe('Changed');
});

it('invalidates on soft delete restore and force delete', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    $user->delete();
    expect(User::query()->find($user->id))->toBeNull();

    $user->restore();
    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    User::query()->find($user->id);
    $user->forceDelete();
    expect(User::query()->find($user->id))->toBeNull();
});

it('defers invalidation until after commit', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    try {
        DB::transaction(function () use ($user): void {
            $user->update(['name' => 'Inside']);
            // Still cached mid-transaction (invalidation deferred)
            expect(User::query()->find($user->id)?->name)->toBe('Ada');
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    DB::transaction(function () use ($user): void {
        $user->update(['name' => 'Committed']);
    });

    expect(User::query()->find($user->id)?->name)->toBe('Committed');
});

it('uses model ttl when configured', function (): void {
    expect((new CacheMissUser)->cacheTtlSeconds())->toBe(120)
        ->and((new User)->cacheTtlSeconds())->toBe(3600);
});

it('registers keys and supports concurrent registry mutations best-effort', function (): void {
    $manager = app(CacheManager::class);

    $manager->registerKey('users', 'key-a', 1);
    $manager->registerKey('users', 'key-b', 2);

    $keys = $manager->readRegistry($manager->tableRegistryKey('users'));
    expect($keys)->toContain('key-a', 'key-b');

    $manager->invalidateTable('users');
    expect($manager->readRegistry($manager->tableRegistryKey('users')))->toBeEmpty();
});
