<?php
/**
 * Pest PHP Configuration
 *
 * @package CrispyTheme\Tests
 */

declare(strict_types=1);

use CrispyTheme\Tests\TestCase;
use Brain\Monkey;

// Load bootstrap.
require_once __DIR__ . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Unit', 'Integration', 'Architecture');

/*
|--------------------------------------------------------------------------
| Test Groups
|--------------------------------------------------------------------------
*/

uses()->group('unit')->in('Unit');
uses()->group('integration')->in('Integration');
uses()->group('architecture')->in('Architecture');

/*
|--------------------------------------------------------------------------
| Before/After Each
|--------------------------------------------------------------------------
*/

uses()
    ->beforeEach(function () {
        setUpBrainMonkey();
    })
    ->afterEach(function () {
        tearDownBrainMonkey();
    })
    ->in('Unit', 'Integration');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidHtml', function () {
    $html = $this->value;

    if (!is_string($html)) {
        return $this->toBeString();
    }

    // Check for at least one HTML tag.
    return $this->toBeString()
        ->and(preg_match('/<[^>]+>/', $html))->toBe(1);
});

expect()->extend('toBeValidUrl', function () {
    return $this->toBeString()
        ->and(filter_var($this->value, FILTER_VALIDATE_URL))->not->toBeFalse();
});

expect()->extend('toBeNonEmptyString', function () {
    return $this->toBeString()
        ->not->toBeEmpty();
});

expect()->extend('toContainHtmlElement', function (string $element) {
    $pattern = sprintf('/<%s[^>]*>/i', preg_quote($element, '/'));
    return $this->toBeString()
        ->and(preg_match($pattern, $this->value))->toBe(1);
});

expect()->extend('toHaveCssClass', function (string $class) {
    $pattern = sprintf('/class="[^"]*\b%s\b[^"]*"/i', preg_quote($class, '/'));
    return $this->toBeString()
        ->and(preg_match($pattern, $this->value))->toBe(1);
});

expect()->extend('toContainMarkdownHeading', function (int $level, string $text) {
    $asserter = new CrispyTheme\Tests\Support\MarkdownAsserter();
    return $this->and($asserter->hasHeading($this->value, $level, $text))->toBeTrue();
});

expect()->extend('toContainCodeBlock', function (?string $language = null) {
    $asserter = new CrispyTheme\Tests\Support\MarkdownAsserter();
    return $this->and($asserter->hasCodeBlock($this->value, $language))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Architecture Testing
|--------------------------------------------------------------------------
*/

// These can be used with Pest's arch() function.
// Example: arch('Content layer only uses Parser and Cache')
//     ->expect('CrispyTheme\Content')
//     ->toOnlyUse(['CrispyTheme\Parser', 'CrispyTheme\Cache']);
