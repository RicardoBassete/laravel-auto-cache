<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    if (! extension_loaded('redis')) {
        $this->markTestSkipped('ext-redis is not installed');
    }

    try {
        $redis = new Redis;
        $redis->connect((string) env('REDIS_HOST', '127.0.0.1'), (int) env('REDIS_PORT', 6379), 0.2);
        $redis->ping();
        $redis->close();
    } catch (Throwable) {
        $this->markTestSkipped('Redis server is not available');
    }

    config()->set('cache.default', 'redis');
    config()->set('cache.stores.redis', [
        'driver' => 'redis',
        'connection' => 'default',
    ]);
    config()->set('database.redis.client', 'phpredis');
    config()->set('database.redis.default', [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 15,
    ]);

    Cache::store('redis')->flush();
});

it('works with the redis cache store', function (): void {
    $user = User::query()->create(['name' => 'Redis', 'email' => 'redis@example.com']);

    expect(User::query()->find($user->id)?->name)->toBe('Redis');

    DB::table('users')->where('id', $user->id)->update(['name' => 'Changed']);

    expect(User::query()->find($user->id)?->name)->toBe('Redis');

    $user->refresh();
    $user->update(['name' => 'Fresh']);

    expect(User::query()->find($user->id)?->name)->toBe('Fresh');
});
