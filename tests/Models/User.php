<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class User extends Model implements AutoCacheable
{
    use AutoCaches;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    /** @var list<string> */
    protected array $cacheInvalidates = [];

    protected bool $cacheMisses = false;

    protected ?int $cacheTtl = null;

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
