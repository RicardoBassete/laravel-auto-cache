<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Console;

use Illuminate\Console\Command;
use RicardoBassete\AutoCache\CacheManager;

final class FlushAutoCacheCommand extends Command
{
    protected $signature = 'auto-cache:flush
                            {table? : Table name to flush. Omit to flush all tracked tables.}';

    protected $description = 'Flush laravel-auto-cache keys for a table or all tracked tables';

    public function handle(CacheManager $manager): int
    {
        $table = $this->argument('table');

        if (is_string($table) && $table !== '') {
            $manager->invalidateTables([$table]);
            $this->components->info("Flushed auto-cache for table [{$table}].");

            return self::SUCCESS;
        }

        $tables = $manager->trackedTables();

        if ($tables === []) {
            $this->components->warn('No tracked auto-cache tables to flush.');

            return self::SUCCESS;
        }

        $manager->invalidateTables($tables);
        $this->components->info('Flushed auto-cache for: '.implode(', ', $tables));

        return self::SUCCESS;
    }
}
