<?php
/**
 * ParserFactory Extended Unit Tests
 *
 * Extends coverage to hit uncovered branches in ParserFactory:
 * - explicit 'basic' type argument
 * - explicit 'extra' type argument
 * - default (no arg) path using apply_filters
 * - allow_unsafe_html filter propagates to parser
 *
 * Existing tests/Unit/ParserFactoryTest.php must NOT be modified.
 *
 * @package CrispyTheme\Tests\Unit\Parser
 */

declare(strict_types=1);

use CrispyTheme\Parser\ParserFactory;
use CrispyTheme\Parser\ParsedownWrapper;
use CrispyTheme\Parser\ParsedownExtraWrapper;
use CrispyTheme\Parser\ParserInterface;
use Brain\Monkey\Functions;

describe('ParserFactory explicit type arguments', function () {
    it('creates a ParsedownWrapper when type is basic', function () {
        Functions\when('apply_filters')->returnArg(2);

        $parser = ParserFactory::create(ParserFactory::TYPE_BASIC);

        expect($parser)->toBeInstanceOf(ParsedownWrapper::class);
        expect($parser)->toBeInstanceOf(ParserInterface::class);
    });

    it('creates a ParsedownExtraWrapper when type is extra', function () {
        Functions\when('apply_filters')->returnArg(2);

        $parser = ParserFactory::create(ParserFactory::TYPE_EXTRA);

        expect($parser)->toBeInstanceOf(ParsedownExtraWrapper::class);
        expect($parser)->toBeInstanceOf(ParserInterface::class);
    });

    it('creates a ParsedownExtraWrapper for unknown type (default branch)', function () {
        Functions\when('apply_filters')->returnArg(2);

        $parser = ParserFactory::create('unknown-type');

        expect($parser)->toBeInstanceOf(ParsedownExtraWrapper::class);
    });

    it('basic parser can parse markdown', function () {
        Functions\when('apply_filters')->returnArg(2);

        $parser = ParserFactory::create(ParserFactory::TYPE_BASIC);
        $html   = $parser->parse('# Test');

        expect($html)->toContain('<h1>Test</h1>');
    });
});

describe('ParserFactory filter interactions', function () {
    it('uses apply_filters to determine type when no argument given', function () {
        // Return 'basic' from the filter to verify the filter is consulted.
        Functions\when('apply_filters')->alias(function (string $hook, mixed $value, mixed ...$args): mixed {
            if ($hook === 'crispytheme_parser_type') {
                return 'basic';
            }
            return $value;
        });

        $parser = ParserFactory::create();

        expect($parser)->toBeInstanceOf(ParsedownWrapper::class);
    });

    it('uses extra type from filter by default', function () {
        Functions\when('apply_filters')->alias(function (string $hook, mixed $value, mixed ...$args): mixed {
            if ($hook === 'crispytheme_parser_type') {
                return 'extra';
            }
            return $value;
        });

        $parser = ParserFactory::create();

        expect($parser)->toBeInstanceOf(ParsedownExtraWrapper::class);
    });

    it('propagates allow_unsafe_html=false from filter to parser', function () {
        Functions\when('apply_filters')->alias(function (string $hook, mixed $value, mixed ...$args): mixed {
            if ($hook === 'crispytheme_allow_unsafe_html') {
                return false;
            }
            return $value;
        });

        $parser = ParserFactory::create(ParserFactory::TYPE_EXTRA);

        expect($parser->allows_unsafe_html())->toBeFalse();
    });

    it('propagates allow_unsafe_html=true from filter to parser', function () {
        Functions\when('apply_filters')->returnArg(2);

        $parser = ParserFactory::create(ParserFactory::TYPE_BASIC);

        expect($parser->allows_unsafe_html())->toBeTrue();
    });
});
