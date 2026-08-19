<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Pest;

use Illuminate\Database\Eloquent\Model;
use RicardoBassete\AutoCache\CacheManager;

use function expect;
use function is_string;
use function is_subclass_of;

/**
 * @internal
 */
final class ExpectationSupport
{
    /**
     * @param  array<int|string, mixed>  $eager
     */
    public static function hasCachedFind(mixed $value, int|string $id, array $eager): bool
    {
        if (! is_string($value) || ! class_exists($value) || ! is_subclass_of($value, Model::class)) {
            expect($value)->toBeString('toHaveCachedFind / toMissCachedFind expect a Model class-string.');

            return false;
        }

        /** @var class-string<Model> $value */
        $model = new $value;
        $manager = app(CacheManager::class);

        /** @var list<string> $eagerList */
        $eagerList = [];

        foreach ($eager as $relation) {
            if (! is_string($relation)) {
                continue;
            }

            $eagerList[] = $relation;
        }

        return $manager->has($manager->recordKey($model->getTable(), $id, $eagerList));
    }
}
