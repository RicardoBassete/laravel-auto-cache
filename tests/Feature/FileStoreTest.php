<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\User;

beforeEach(function (): void {
    config()->set('cache.default', 'file');
    config()->set('cache.stores.file', [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
    ]);

    if (! is_dir(storage_path('framework/cache/data'))) {
        mkdir(storage_path('framework/cache/data'), 0777, true);
    }

    Cache::flush();
});

it('works with the file cache store', function (): void {
    $user = User::query()->create(['name' => 'File', 'email' => 'file@example.com']);

    expect(User::query()->find($user->id)?->name)->toBe('File');

    DB::table('users')->where('id', $user->id)->update(['name' => 'Disk']);

    expect(User::query()->find($user->id)?->name)->toBe('File');
});
