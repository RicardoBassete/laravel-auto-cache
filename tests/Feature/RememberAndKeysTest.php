<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    Cache::flush();
});

it('lists registered keys via autoCacheKeys', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(User::autoCacheKeys())->toBeEmpty();

    User::query()->find($user->id);

    expect(User::autoCacheKeys())->not->toBeEmpty()
        ->and(User::autoCacheKeys()[0])->toContain(':users:id:'.$user->id);
});

it('remembers values under the record key via autoCacheRemember', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $calls = 0;

    $first = User::autoCacheRemember($user->id, function () use (&$calls, $user): string {
        $calls++;

        return 'payload-'.$user->id;
    });

    $second = User::autoCacheRemember($user->id, function () use (&$calls): string {
        $calls++;

        return 'should-not-run';
    });

    expect($first)->toBe('payload-'.$user->id)
        ->and($second)->toBe('payload-'.$user->id)
        ->and($calls)->toBe(1);

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);
    User::autoCacheForget($user->id);

    $third = User::autoCacheRemember($user->id, function () use (&$calls): string {
        $calls++;

        return 'fresh';
    });

    expect($third)->toBe('fresh')->and($calls)->toBe(2);
});
