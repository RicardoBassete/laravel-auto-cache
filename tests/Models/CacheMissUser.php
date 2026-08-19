<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class CacheMissUser extends Model implements AutoCacheable
{
    use AutoCaches;

    protected $table = 'users';

    protected $guarded = [];

    protected bool $cacheMisses = true;

    protected ?int $cacheTtl = 120;

    /** @var list<string> */
    protected array $cacheInvalidates = [];
}
