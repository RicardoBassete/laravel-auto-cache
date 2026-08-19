<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RicardoBassete\AutoCache\Tests\Models\Post;
use RicardoBassete\AutoCache\Tests\Models\User;
use RicardoBassete\AutoCache\Tests\Models\UuidArticle;

beforeEach(function (): void {
    Cache::flush();
});

it('caches findOrFail and firstOrFail through find/first', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'active' => true]);

    expect(User::query()->findOrFail($user->id)->name)->toBe('Ada')
        ->and(User::query()->where('active', true)->firstOrFail()->name)->toBe('Ada');

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);

    expect(User::query()->findOrFail($user->id)->name)->toBe('Ada')
        ->and(User::query()->where('active', true)->firstOrFail()->name)->toBe('Ada');

    expect(fn () => User::query()->findOrFail(999))->toThrow(ModelNotFoundException::class);
});

it('caches findMany entries per primary key', function (): void {
    $a = User::query()->create(['name' => 'A', 'email' => 'a@example.com']);
    $b = User::query()->create(['name' => 'B', 'email' => 'b@example.com']);

    $first = User::query()->findMany([$a->id, $b->id]);
    expect($first)->toHaveCount(2);

    DB::table('users')->where('id', $a->id)->update(['name' => 'Changed']);

    $second = User::query()->findMany([$a->id, $b->id]);
    expect($second->firstWhere('id', $a->id)?->name)->toBe('A')
        ->and($second->firstWhere('id', $b->id)?->name)->toBe('B');
});

it('caches models with string uuid primary keys', function (): void {
    $article = UuidArticle::query()->create(['title' => 'Hello']);

    expect($article->id)->toBeString()
        ->and(UuidArticle::query()->find($article->id)?->title)->toBe('Hello');

    DB::table('uuid_articles')->where('id', $article->id)->update(['title' => 'Changed']);

    expect(UuidArticle::query()->find($article->id)?->title)->toBe('Hello');

    $article->update(['title' => 'Fresh']);

    expect(UuidArticle::query()->find($article->id)?->title)->toBe('Fresh');
});

it('forgets a single record via autoCacheForget', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);
    expect(User::query()->find($user->id)?->name)->toBe('Ada');

    User::autoCacheForget($user->id);

    expect(User::query()->find($user->id)?->name)->toBe('Hidden');
});

it('flushes table caches via autoCacheFlush', function (): void {
    User::query()->create(['name' => 'A', 'email' => 'a@example.com', 'active' => true]);
    User::query()->create(['name' => 'B', 'email' => 'b@example.com', 'active' => true]);

    expect(User::query()->where('active', true)->count())->toBe(2);

    DB::table('users')->update(['active' => false]);
    expect(User::query()->where('active', true)->count())->toBe(2);

    User::autoCacheFlush();

    expect(User::query()->where('active', true)->count())->toBe(0);
});

it('cascades when autoCacheForget is called on a child model', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $post = Post::query()->create(['user_id' => $user->id, 'title' => 'First']);

    expect(User::query()->with('posts')->find($user->id)?->posts)->toHaveCount(1);

    DB::table('posts')->where('id', $post->id)->update(['title' => 'Changed']);
    Post::autoCacheForget($post->id);

    expect(User::query()->with('posts')->find($user->id)?->posts->first()?->title)->toBe('Changed');
});

it('forgets the current instance via autoCacheForgetSelf', function (): void {
    $user = User::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    User::query()->find($user->id);

    DB::table('users')->where('id', $user->id)->update(['name' => 'Hidden']);
    $user->autoCacheForgetSelf();

    expect(User::query()->find($user->id)?->name)->toBe('Hidden');
});
