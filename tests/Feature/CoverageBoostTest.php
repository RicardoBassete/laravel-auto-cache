<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Support\AnalysedAutoCacheModel;
use RicardoBassete\AutoCache\Tests\Models\Post;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('caches value and sum and serves them on hit', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'active' => true]);
    User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'active' => true]);

    expect(User::query()->where('active', true)->value('name'))->toBe('A')
        ->and(User::query()->sum('active'))->toBe(2);

    DB::table('users')->update(['name' => 'Z', 'active' => false]);

    expect(User::query()->where('active', true)->value('name'))->toBe('A')
        ->and(User::query()->sum('active'))->toBe(2);
});

it('re-enables caching with withCache after withoutCache', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $builder = User::withoutCache()->withCache();
    expect($builder->cachingEnabled())->toBeTrue();

    expect($builder->find($user->id)?->name)->toBe('Ada');

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(User::query()->find($user->id)?->name)->toBe('Ada');
});

it('finds many ids without using record cache path', function (): void {
    $a = User::query()->create(['name' => 'A', 'email' => 'a@example.com']);
    $b = User::query()->create(['name' => 'B', 'email' => 'b@example.com']);

    $found = User::query()->find([$a->id, $b->id]);
    expect($found)->toHaveCount(2);
});

it('caches first queries and hits cache on second call', function (): void {
    User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);

    expect(User::query()->where('active', true)->first()?->name)->toBe('Ada');

    DB::table('users')->where('active', true)->update(['name' => 'Changed']);

    expect(User::query()->where('active', true)->first()?->name)->toBe('Ada');
});

it('uses table key for full-table gets without soft deletes', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'One']);

    $manager = app(CacheManager::class);
    $key = $manager->tableKey('posts');

    expect(Post::query()->get())->toHaveCount(1)
        ->and($manager->has($key))->toBeTrue();

    DB::table('posts')->insert([
        'user_id' => $user->id,
        'title' => 'Two',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Post::query()->get())->toHaveCount(1);
});

it('invalidates via insert and upsert mass mutations', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'One']);

    expect(Post::query()->count())->toBe(1);

    Post::query()->insert([
        'user_id' => $user->id,
        'title' => 'Two',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Post::query()->count())->toBe(2);

    Post::query()->upsert([
        [
            'id' => 1,
            'user_id' => $user->id,
            'title' => 'Upserted',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ], ['id'], ['title']);

    expect(Post::query()->find(1)?->title)->toBe('Upserted');
});

it('invalidates via mass delete', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'One']);
    Post::query()->create(['user_id' => $user->id, 'title' => 'Two']);

    expect(Post::query()->count())->toBe(2);

    Post::query()->where('title', 'One')->delete();

    expect(Post::query()->count())->toBe(1);
});

it('bypasses scalar caches when withoutCache is used', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'active' => true]);

    expect(User::query()->count())->toBe(1);

    User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'active' => true]);

    expect(User::withoutCache()->count())->toBe(2)
        ->and(User::withoutCache()->exists())->toBeTrue()
        ->and(User::withoutCache()->value('name'))->toBe('A')
        ->and(User::withoutCache()->sum('active'))->toBe(2)
        ->and(User::withoutCache()->pluck('email'))->toHaveCount(2);
});

it('covers CacheManager helpers remember forget and named store', function (): void {
    config()->set('auto-cache.store', 'array');
    config()->set('auto-cache.prefix', 'custom');
    config()->set('auto-cache.ttl', '120');
    config()->set('auto-cache.lock_seconds', '3');

    $manager = app(CacheManager::class);

    expect($manager->prefix())->toBe('custom')
        ->and($manager->defaultTtl())->toBe(120)
        ->and($manager->lockSeconds())->toBe(3)
        ->and($manager->tableKey('users', ['posts']))->toContain('users:all:with:');

    $key = $manager->recordKey('users', 1);
    $first = $manager->remember($key, 60, 'users', 1, fn (): string => 'hit');
    $second = $manager->remember($key, 60, 'users', 1, fn (): string => 'miss');

    expect($first)->toBe('hit')
        ->and($second)->toBe('hit');

    $manager->forget($key);
    expect($manager->has($key))->toBeFalse();

    $manager->put($key, 'again', 60, 'users', 1);
    $manager->invalidateRecord('users', 1);
    expect($manager->has($key))->toBeFalse();
});

it('uses AnalysedAutoCacheModel defaults when properties are absent', function (): void {
    $model = new AnalysedAutoCacheModel;

    expect($model->cacheTtlSeconds())->toBe(3600)
        ->and($model->shouldCacheMisses())->toBeFalse()
        ->and($model->cacheInvalidatesTables())->toBe([]);
});

it('does not cache empty collections by default', function (): void {
    $manager = app(CacheManager::class);

    expect(Post::query()->where('title', 'missing')->get())->toHaveCount(0);

    $keys = $manager->readRegistry($manager->tableRegistryKey('posts'));
    expect($keys)->toBeEmpty();
});
