<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

final class UuidArticle extends Model implements AutoCacheable
{
    use AutoCaches;
    use HasUuids;

    protected $table = 'uuid_articles';

    protected $guarded = [];

    protected ?int $cacheTtl = null;

    protected bool $cacheMisses = false;

    /** @var list<string> */
    protected array $cacheInvalidates = [];
}
