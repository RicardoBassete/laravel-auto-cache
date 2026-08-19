<?php

declare(strict_types=1);

namespace RicardoBassete\AutoCache\Debugbar;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use RicardoBassete\AutoCache\Support\AutoCacheCollector;

/**
 * Optional Barryvdh Debugbar panel. Instantiated only when Debugbar is installed.
 *
 * @internal
 */
final class AutoCacheDebugbarCollector extends DataCollector implements Renderable
{
    public function __construct(
        private readonly AutoCacheCollector $collector,
    ) {}

    public function collect(): array
    {
        $entries = $this->collector->entries();

        return [
            'count' => count($entries),
            'hits' => $this->collector->hits(),
            'misses' => $this->collector->misses(),
            'invalidations' => $this->collector->invalidations(),
            'entries' => array_map(static function (array $entry): array {
                return [
                    'type' => $entry['type'],
                    'table' => $entry['table'],
                    'key' => $entry['key'],
                    'record_id' => $entry['record_id'],
                    'scope' => $entry['scope'],
                ];
            }, $entries),
        ];
    }

    public function getName(): string
    {
        return 'auto-cache';
    }

    public function getWidgets(): array
    {
        return [
            'auto-cache' => [
                'icon' => 'database',
                'widget' => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
                'map' => 'auto-cache',
                'default' => '[]',
            ],
            'auto-cache:badge' => [
                'map' => 'auto-cache.count',
                'default' => 0,
            ],
        ];
    }
}
