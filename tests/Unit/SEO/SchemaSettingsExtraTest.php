<?php
/**
 * SchemaSettings Extra Unit Tests
 *
 * Covers additional branch paths in get_same_as after the refactor to
 * explode() (CRLF/CR normalised to LF before splitting). Tests exercise
 * the array_values(array_filter(...)) path with blank-only, mixed, and
 * single-URL inputs to reach 100 % line coverage on SchemaSettings.
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('SchemaSettings (extra coverage)', function () {

    beforeEach(function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );
    });

    // -----------------------------------------------------------------------
    // get_same_as — paths that exercise array_values / array_filter
    // -----------------------------------------------------------------------

    it('filters blank-only lines and re-indexes the result array', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "\n\n   \nhttps://example.com/profile\n   \n"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        // Only the non-blank, trimmed URL should survive.
        expect($same_as)->toBe(['https://example.com/profile']);
    });

    it('returns an array with sequential integer keys starting at 0', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "\nhttps://a.example\n\nhttps://b.example\n"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        // array_values() must ensure keys are 0 and 1.
        expect(array_keys($same_as))->toBe([0, 1]);
        expect($same_as[0])->toBe('https://a.example');
        expect($same_as[1])->toBe('https://b.example');
    });

    it('returns an empty array when input is all whitespace', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key ? "   \n   \n   " : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        expect($same_as)->toBe([]);
    });

    it('handles a single URL with no newlines', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key ? 'https://single.example' : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        expect($same_as)->toBe(['https://single.example']);
    });

    it('normalises CRLF line endings via str_replace before splitting', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://first.example\r\nhttps://second.example"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        expect($same_as)->toBe(['https://first.example', 'https://second.example']);
    });

    it('normalises bare CR line endings via str_replace before splitting', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://a.example\rhttps://b.example"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as  = $settings->get_same_as();

        expect($same_as)->toBe(['https://a.example', 'https://b.example']);
    });
});
