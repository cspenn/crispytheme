<?php
/**
 * Template Tags (global helper functions) Unit Tests.
 *
 * @package CrispyTheme\Tests\Unit
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

// Define the global helper functions. add_shortcode is not defined at collection
// time, so the guarded add_shortcode() call is skipped — only the functions load.
require_once dirname(__DIR__, 2) . '/inc/template-tags.php';

describe('crispytheme_get_subscriber_count', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->alias(static fn(string $tag, mixed $value): mixed => $value);
    });

    it('returns the transient value when one is cached', function () {
        Functions\when('get_transient')->justReturn('300,000');
        expect(crispytheme_get_subscriber_count())->toBe('300,000');
    });

    it('falls back to the option when the transient is empty', function () {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = ''): mixed =>
                'crispytheme_newsletter_subscriber_count' === $key ? '294,000+' : $default
        );
        expect(crispytheme_get_subscriber_count())->toBe('294,000+');
    });

    it('lets the crispytheme_subscriber_count filter override the value', function () {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('100');
        Functions\when('apply_filters')->alias(
            static fn(string $tag, mixed $value): mixed =>
                'crispytheme_subscriber_count' === $tag ? '999,999' : $value
        );
        expect(crispytheme_get_subscriber_count())->toBe('999,999');
    });

    it('trims surrounding whitespace', function () {
        Functions\when('get_transient')->justReturn('  500  ');
        expect(crispytheme_get_subscriber_count())->toBe('500');
    });
});

describe('crispytheme_get_booking_url', function () {
    it('returns the configured booking URL', function () {
        Functions\when('get_option')->justReturn('/book-christopher/');
        expect(crispytheme_get_booking_url())->toBe('/book-christopher/');
    });

    it('falls back to /contact/ when the option is blank', function () {
        Functions\when('get_option')->justReturn('   ');
        expect(crispytheme_get_booking_url())->toBe('/contact/');
    });
});

describe('crispytheme_copyright_shortcode', function () {
    it('renders the current year and site name', function () {
        Functions\when('wp_date')->justReturn('2026');
        Functions\when('get_bloginfo')->justReturn('Test Site');
        Functions\when('esc_html')->returnArg();
        expect(crispytheme_copyright_shortcode())->toBe('&copy; 2026 Test Site');
    });
});
