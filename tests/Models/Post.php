<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class Post extends Model implements AutoCacheable
{
    use AutoCaches;

    protected $table = 'posts';

    protected $guarded = [];

    /** @var list<string> */
    protected array $cacheInvalidates = ['users'];

    protected bool $cacheMisses = false;

    protected ?int $cacheTtl = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
