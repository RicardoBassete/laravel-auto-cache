<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\SilentUser;

beforeEach(function (): void {
    Cache::flush();
});

it('skips invalidation when only silent attributes change', function (): void {
    $user = SilentUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);

    expect(SilentUser::query()->find($user->id)?->name)->toBe('Ada');

    $user->update(['name' => 'Grace']);

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    // Still cached under previous find key (invalidation was skipped for silent-only update).
    expect(SilentUser::query()->find($user->id)?->name)->toBe('Ada');
});

it('invalidates when a non-silent attribute changes', function (): void {
    $user = SilentUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);

    expect(SilentUser::query()->find($user->id)?->active)->toBeTrue();

    $user->update(['active' => false]);

    expect(SilentUser::query()->find($user->id)?->active)->toBeFalse();
});
