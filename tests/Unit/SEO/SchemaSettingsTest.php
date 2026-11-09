<?php
/**
 * SchemaSettings Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('SchemaSettings', function () {
    beforeEach(function () {
        // Default mocks — options return their defaults; home_url resolves simply.
        // Individual tests may override get_option for specific keys.
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );
    });

    it('returns default person name when option not set', function () {
        $settings = new SchemaSettings();
        expect($settings->get_person_name())->toBe('Christopher S. Penn');
    });

    it('returns configured person name from options', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_person_name' === $key ? 'Test Name' : $default
        );

        $settings = new SchemaSettings();
        expect($settings->get_person_name())->toBe('Test Name');
    });

    it('returns empty string for person image when not configured', function () {
        $settings = new SchemaSettings();
        expect($settings->get_person_image())->toBe('');
    });

    it('returns configured person image URL', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_person_image' === $key ? 'https://example.com/photo.jpg' : $default
        );

        $settings = new SchemaSettings();
        expect($settings->get_person_image())->toBe('https://example.com/photo.jpg');
    });

    it('splits sameAs newline-delimited URLs correctly (LF)', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://twitter.com/cspenn\nhttps://linkedin.com/in/cspenn"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as = $settings->get_same_as();

        expect($same_as)->toBe([
            'https://twitter.com/cspenn',
            'https://linkedin.com/in/cspenn',
        ]);
    });

    it('splits sameAs CRLF line endings correctly', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://twitter.com/cspenn\r\nhttps://linkedin.com/in/cspenn"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as = $settings->get_same_as();

        expect($same_as)->toHaveCount(2);
        expect($same_as[0])->toBe('https://twitter.com/cspenn');
        expect($same_as[1])->toBe('https://linkedin.com/in/cspenn');
    });

    it('drops empty lines from sameAs', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://twitter.com/cspenn\n\nhttps://linkedin.com/in/cspenn\n"
                    : $default
        );

        $settings = new SchemaSettings();
        $same_as = $settings->get_same_as();

        expect($same_as)->toHaveCount(2);
    });

    it('returns the two profile links as the default sameAs list', function () {
        $settings = new SchemaSettings();
        $same_as = $settings->get_same_as();

        expect($same_as)->toBe([
            'https://www.linkedin.com/in/christopherspenn/',
            'https://almosttimely.substack.com',
        ]);
    });

    it('returns an absolute booking URL unchanged, with its fragment', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_booking_url' === $key ? 'https://example.com/public-speaking/#check-my-dates' : $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );

        $settings = new SchemaSettings();

        expect($settings->get_booking_url())->toBe('https://example.com/public-speaking/#check-my-dates');
    });

    it('does not wrap a foreign absolute booking URL in home_url', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_booking_url' === $key ? 'https://form.example.org/book' : $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );

        $settings = new SchemaSettings();

        expect($settings->get_booking_url())->toBe('https://form.example.org/book');
    });

    it('resolves booking URL relative path to absolute URL', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_booking_url' === $key ? '/contact/' : $default
        );

        $settings = new SchemaSettings();
        expect($settings->get_booking_url())->toBe('https://example.com/contact/');
    });

    it('returns home URL', function () {
        $settings = new SchemaSettings();
        expect($settings->get_home_url())->toBe('https://example.com/');
    });

    it('returns empty string for video URL when not configured', function () {
        $settings = new SchemaSettings();
        expect($settings->get_video_url())->toBe('');
    });
});
