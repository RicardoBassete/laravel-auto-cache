<?php

declare(strict_types=1);

$skillsRoot = dirname(__DIR__, 2).'/resources/boost/skills';
$guidelines = dirname(__DIR__, 2).'/resources/boost/guidelines/core.blade.php';

$expected = [
    'laravel-auto-cache',
    'laravel-auto-cache-opt-in',
    'laravel-auto-cache-cascade',
    'laravel-auto-cache-ttl-misses',
    'laravel-auto-cache-bypass',
    'laravel-auto-cache-silent-attributes',
    'laravel-auto-cache-flush-lists',
    'laravel-auto-cache-pest',
    'laravel-auto-cache-collector',
];

it('exposes Boost third-party skills under resources/boost/skills', function () use ($skillsRoot, $expected): void {
    expect(is_dir($skillsRoot))->toBeTrue();

    foreach ($expected as $name) {
        $skillFile = $skillsRoot.'/'.$name.'/SKILL.md';
        expect($skillFile)->toBeFile();

        $content = file_get_contents($skillFile);
        expect($content)->toMatch('/^---\s*\n/s')
            ->and($content)->toContain("name: {$name}")
            ->and($content)->toMatch('/description:\s*>?-/s');
    }
});

it('exposes Boost third-party guidelines at resources/boost/guidelines/core.blade.php', function () use ($guidelines): void {
    expect($guidelines)->toBeFile();

    $content = file_get_contents($guidelines);
    expect($content)->toContain('laravel-auto-cache')
        ->and($content)->toContain('Activate `laravel-auto-cache');
});

it('keeps .agents skills in sync with Boost resources for local agents', function () use ($skillsRoot, $expected): void {
    $agentsRoot = dirname(__DIR__, 2).'/.agents/skills';

    foreach ($expected as $name) {
        $boost = file_get_contents($skillsRoot.'/'.$name.'/SKILL.md');
        $agents = file_get_contents($agentsRoot.'/'.$name.'/SKILL.md');
        expect($agents)->toBe($boost);
    }
});
