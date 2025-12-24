<?php
/**
 * Layer Dependency Tests
 *
 * Validates architectural layer boundaries using Pest's architecture testing.
 * These tests mirror the rules defined in deptrac.yaml.
 *
 * @package CrispyTheme\Tests\Architecture
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Layer Dependency Rules
|--------------------------------------------------------------------------
|
| These tests enforce the layer boundaries defined in deptrac.yaml:
|
| Theme → Content, Admin, CLI, DarkMode, Parser, Cache
| Admin → Content, Parser
| CLI → Content, Parser
| Content → Parser, Cache
| DarkMode → (no dependencies)
| Parser → (no dependencies)
| Cache → (no dependencies)
|
*/

describe('Architecture: Layer Dependencies', function () {

    test('Parser layer has no internal dependencies')
        ->expect('CrispyTheme\Parser')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\Admin',
            'CrispyTheme\CLI',
            'CrispyTheme\Content',
            'CrispyTheme\DarkMode',
            'CrispyTheme\Cache',
        ]);

    test('Cache layer has no internal dependencies')
        ->expect('CrispyTheme\Cache')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\Admin',
            'CrispyTheme\CLI',
            'CrispyTheme\Content',
            'CrispyTheme\DarkMode',
            'CrispyTheme\Parser',
        ]);

    test('DarkMode layer has no internal dependencies')
        ->expect('CrispyTheme\DarkMode')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\Admin',
            'CrispyTheme\CLI',
            'CrispyTheme\Content',
            'CrispyTheme\Parser',
            'CrispyTheme\Cache',
        ]);

    test('Content layer only depends on Parser and Cache')
        ->expect('CrispyTheme\Content')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\Admin',
            'CrispyTheme\CLI',
            'CrispyTheme\DarkMode',
        ]);

    test('Admin layer only depends on Content and Parser')
        ->expect('CrispyTheme\Admin')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\CLI',
            'CrispyTheme\DarkMode',
            'CrispyTheme\Cache',
        ]);

    test('CLI layer only depends on Content and Parser')
        ->expect('CrispyTheme\CLI')
        ->not->toUse([
            'CrispyTheme\Theme',
            'CrispyTheme\Admin',
            'CrispyTheme\DarkMode',
            'CrispyTheme\Cache',
        ]);

});

/*
|--------------------------------------------------------------------------
| Naming Convention Tests
|--------------------------------------------------------------------------
*/

describe('Architecture: Naming Conventions', function () {

    test('all classes are strict types')
        ->expect('CrispyTheme')
        ->toUseStrictTypes();

    test('all test classes are strict types')
        ->expect('CrispyTheme\Tests')
        ->toUseStrictTypes();

});

/*
|--------------------------------------------------------------------------
| Code Quality Tests
|--------------------------------------------------------------------------
*/

describe('Architecture: Code Quality', function () {

    test('source code should not use debug functions')
        ->expect('CrispyTheme')
        ->not->toUse(['var_dump', 'print_r', 'dd', 'dump', 'error_log']);

    test('source code should not use die or exit')
        ->expect('CrispyTheme')
        ->not->toUse(['die', 'exit']);

});

/*
|--------------------------------------------------------------------------
| Interface Compliance Tests
|--------------------------------------------------------------------------
*/

describe('Architecture: Interface Compliance', function () {

    test('parser implementations must implement ParserInterface')
        ->expect('CrispyTheme\Parser')
        ->classes()
        ->toImplement('CrispyTheme\Parser\ParserInterface')
        ->ignoring('CrispyTheme\Parser\ParserInterface')
        ->ignoring('CrispyTheme\Parser\ParserFactory');

});
