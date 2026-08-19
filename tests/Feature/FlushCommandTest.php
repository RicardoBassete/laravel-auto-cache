<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\CacheManager;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('flushes a single table via auto-cache:flush', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    $this->artisan('auto-cache:flush', ['table' => 'users'])
        ->assertSuccessful();

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(User::query()->find($user->id)?->name)->toBe('Hidden');
});

it('flushes all tracked tables when table is omitted', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    User::query()->find($user->id);

    expect(app(CacheManager::class)->trackedTables())->toContain('users');

    $this->artisan('auto-cache:flush')
        ->assertSuccessful();

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(User::query()->find($user->id)?->name)->toBe('Hidden');
});

it('succeeds with a warning when nothing is tracked', function (): void {
    $this->artisan('auto-cache:flush')
        ->assertSuccessful();
});
