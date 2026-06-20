<?php
/**
 * PatternManager Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Patterns
 */

declare(strict_types=1);

use CrispyTheme\Patterns\PatternManager;
use Brain\Monkey\Functions;

describe('PatternManager::init', function () {
    beforeEach(function () {
        $this->setupHooks();
    });

    it('registers the register_pattern_categories action on init', function () {
        $manager = new PatternManager();
        $manager->init();

        $actions = $this->getRegisteredActions('init');

        $found = false;
        foreach ($actions as $action) {
            if (
                is_array($action['callback'])
                && $action['callback'][0] === $manager
                && $action['callback'][1] === 'register_pattern_categories'
            ) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue('register_pattern_categories should be hooked to init');
    });

    it('registers the register_patterns_from_directory action on init with priority 11', function () {
        $manager = new PatternManager();
        $manager->init();

        $actions = $this->getRegisteredActions('init');

        $found = false;
        foreach ($actions as $action) {
            if (
                is_array($action['callback'])
                && $action['callback'][0] === $manager
                && $action['callback'][1] === 'register_patterns_from_directory'
                && $action['priority'] === 11
            ) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue('register_patterns_from_directory should be hooked to init at priority 11');
    });
});

describe('PatternManager::register_pattern_categories', function () {
    it('calls register_block_pattern_category for each expected category', function () {
        $registered = [];

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_pattern_category')->alias(
            function (string $slug, array $args) use (&$registered): void {
                $registered[$slug] = $args;
            }
        );

        $manager = new PatternManager();
        $manager->register_pattern_categories();

        $expectedSlugs = [
            'crispytheme-homepage',
            'crispytheme-trust',
            'crispytheme-newsletter',
            'crispytheme-speaking',
            'crispytheme-code',
        ];

        foreach ($expectedSlugs as $slug) {
            expect(array_key_exists($slug, $registered))->toBeTrue(
                "Category slug '{$slug}' should be registered"
            );
        }
    });

    it('registers exactly five categories', function () {
        $count = 0;

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_pattern_category')->alias(
            function () use (&$count): void {
                $count++;
            }
        );

        $manager = new PatternManager();
        $manager->register_pattern_categories();

        expect($count)->toBe(5);
    });

    it('passes a label for each category', function () {
        $labels = [];

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_pattern_category')->alias(
            function (string $slug, array $args) use (&$labels): void {
                $labels[$slug] = $args['label'] ?? null;
            }
        );

        $manager = new PatternManager();
        $manager->register_pattern_categories();

        foreach ($labels as $slug => $label) {
            expect($label)->toBeString(
                "Category '{$slug}' should have a string label"
            );
            expect($label)->not->toBeEmpty(
                "Category '{$slug}' label should not be empty"
            );
        }
    });

    it('uses the crispy-theme text domain for translations', function () {
        $domains = [];

        Functions\when('__')->alias(function (string $text, string $domain = 'default') use (&$domains): string {
            $domains[] = $domain;
            return $text;
        });
        Functions\when('register_block_pattern_category')->justReturn(null);

        $manager = new PatternManager();
        $manager->register_pattern_categories();

        foreach ($domains as $domain) {
            expect($domain)->toBe('crispy-theme');
        }
    });
});

/**
 * Stub all WordPress functions called by pattern files so they can be safely included.
 *
 * crispytheme_get_booking_url is a user-defined function that may already be defined
 * when pattern files have been require_once'd earlier in the test process. We only stub
 * it when it isn't defined yet to avoid a Patchwork DefinedTooEarly exception.
 */
function stubPatternFileFunctions(): void
{
    Functions\when('register_block_pattern')->justReturn(null);
    Functions\when('get_option')->returnArg(2);
    // get_transient is used by crispytheme_get_subscriber_count() in template-tags.php,
    // which is included when the patterns directory files are require_once'd.
    Functions\when('get_transient')->justReturn(false);
    Functions\when('apply_filters')->returnArg(2);
    if (!function_exists('crispytheme_get_booking_url')) {
        Functions\when('crispytheme_get_booking_url')->justReturn('/contact/');
    }
    Functions\when('esc_url')->returnArg(1);
    Functions\when('esc_attr')->returnArg(1);
    Functions\when('esc_attr__')->returnArg(1);
    Functions\when('esc_html')->returnArg(1);
    Functions\when('esc_html__')->returnArg(1);
    Functions\when('wp_kses_post')->returnArg(1);
}

describe('PatternManager::register_patterns_from_directory', function () {
    // NOTE: Pattern files are included via require_once, so they execute only on the
    // first inclusion in a PHP process. Tests in this describe block that count
    // register_block_pattern calls must run before any test that has already triggered
    // file inclusion. Pest runs tests top-to-bottom within a describe, so the counting
    // test is deliberately placed first.

    it('calls register_block_pattern at least once when patterns directory is populated', function () {
        $callCount = 0;

        Functions\when('register_block_pattern')->alias(function () use (&$callCount): void {
            $callCount++;
        });
        Functions\when('get_option')->returnArg(2);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('apply_filters')->returnArg(2);
        if (!function_exists('crispytheme_get_booking_url')) {
            Functions\when('crispytheme_get_booking_url')->justReturn('/contact/');
        }
        Functions\when('esc_url')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_attr__')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('wp_kses_post')->returnArg(1);

        $manager = new PatternManager();
        $manager->register_patterns_from_directory();

        // The real patterns/ directory has files; at least one must be registered.
        expect($callCount)->toBeGreaterThan(0);
    });

    it('completes without throwing when patterns directory exists', function () {
        // Pattern files already included via require_once above; this just verifies
        // that a second call returns cleanly (glob + loop, no pattern body re-runs).
        stubPatternFileFunctions();

        $manager = new PatternManager();

        expect(fn () => $manager->register_patterns_from_directory())->not->toThrow(\Throwable::class);
    });

    it('returns cleanly on repeated calls (require_once idempotence)', function () {
        stubPatternFileFunctions();

        $manager = new PatternManager();

        // Call twice — require_once ensures pattern bodies are not re-run.
        $manager->register_patterns_from_directory();
        $result = null;
        try {
            $manager->register_patterns_from_directory();
            $result = 'ok';
        } catch (\Throwable $e) {
            $result = $e->getMessage();
        }

        expect($result)->toBe('ok');
    });
});
