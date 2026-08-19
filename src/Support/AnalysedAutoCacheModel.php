<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Support;

use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\Concerns\AutoCaches;
use RicardoBassete\AutoCache\Contracts\AutoCacheable;

/**
 * Concrete model so PHPStan analyses the AutoCaches trait.
 *
 * @internal
 */
final class AnalysedAutoCacheModel extends Model implements AutoCacheable
{
    use AutoCaches;

    /** @var string */
    protected $table = 'analysed_auto_cache_models';
}
