<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Pest;

use Pest\Contracts\Plugins\Bootable;

final class Plugin implements Bootable
{
    public function boot(): void
    {
        require_once __DIR__.'/Expectations.php';
    }
}
