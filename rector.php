<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__])
    ->withSkip([
        __DIR__ . '/assets/',
        __DIR__ . '/lib/',
        __DIR__ . '/node_modules/',
        // Declarations mirroring Grav, Twig and shortcode-core, only meant for PHPStan
        __DIR__ . '/stubs/',
        __DIR__ . '/tools/',
        __DIR__ . '/vendor/',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
