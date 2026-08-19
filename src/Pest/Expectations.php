<?php

declare(strict_types=1);

use Pest\Expectation;
use RicardoBassete\AutoCache\Pest\ExpectationSupport;

/*
|--------------------------------------------------------------------------
| AutoCache Pest expectations
|--------------------------------------------------------------------------
|
| expect(User::class)->toHaveCachedFind($id)
| expect(User::class)->toMissCachedFind($id)
|
*/

expect()->extend('toHaveCachedFind', function (int|string $id, array $eager = []): Expectation {
    $has = ExpectationSupport::hasCachedFind($this->value, $id, $eager);

    expect($has)->toBeTrue();

    return $this;
});

expect()->extend('toMissCachedFind', function (int|string $id, array $eager = []): Expectation {
    $has = ExpectationSupport::hasCachedFind($this->value, $id, $eager);

    expect($has)->toBeFalse();

    return $this;
});
