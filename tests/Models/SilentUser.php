<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class SilentUser extends Model implements AutoCacheable
{
    use AutoCaches;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected ?int $cacheTtl = null;

    protected bool $cacheMisses = false;

    /** @var list<string> */
    protected array $cacheInvalidates = [];

    /** @var list<string> */
    protected array $cacheSilentAttributes = ['name'];
}
