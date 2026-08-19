<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\FlushListsUser;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('flushes list caches on single-row save when cacheFlushListsOnSave is true', function (): void {
    $user = FlushListsUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);
    FlushListsUser::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'active' => true]);

    expect(FlushListsUser::query()->find($user->id)?->name)->toBe('Ada')
        ->and(FlushListsUser::query()->where('active', true)->get())->toHaveCount(2);

    $user->update(['name' => 'Updated']);

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(FlushListsUser::query()->find($user->id)?->name)->toBe('Hidden')
        ->and(FlushListsUser::query()->where('active', true)->get()->firstWhere('id', $user->id)?->name)->toBe('Hidden');
});

it('keeps other record find caches warm when flushing lists on save', function (): void {
    $ada = FlushListsUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);
    $other = FlushListsUser::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'active' => true]);

    expect(FlushListsUser::query()->find($other->id)?->name)->toBe('Other');

    $ada->update(['name' => 'Updated']);

    DB::table('users')->where('id', $other->id)->update(['name' => 'HiddenOther']);

    expect(FlushListsUser::query()->find($other->id)?->name)->toBe('Other');
});

it('flushes only list keys via autoCacheFlushLists', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);

    expect(User::query()->find($user->id)?->name)->toBe('Ada')
        ->and(User::query()->where('active', true)->count())->toBe(1);

    User::autoCacheFlushLists();

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden', 'active' => false]);

    expect(User::query()->find($user->id)?->name)->toBe('Ada')
        ->and(User::query()->where('active', true)->count())->toBe(0);
});
