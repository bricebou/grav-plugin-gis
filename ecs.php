<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__])
    ->withSkip([
        __DIR__ . '/assets/',
        __DIR__ . '/lib/',
        __DIR__ . '/node_modules/',
        __DIR__ . '/tools/',
        __DIR__ . '/vendor/',
    ])
    ->withPreparedSets(
        psr12: true,
        common: true,
        cleanCode: true,
    )
    ->withConfiguredRule(OrderedImportsFixer::class, [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha',
    ]);
