<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class FlushListsUser extends Model implements AutoCacheable
{
    use AutoCaches;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    protected ?int $cacheTtl = null;

    protected bool $cacheMisses = false;

    protected bool $cacheFlushListsOnSave = true;

    /** @var list<string> */
    protected array $cacheInvalidates = [];
}
