<?php
/**
 * SignupBlock Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Newsletter
 */

declare(strict_types=1);

use CrispyTheme\Newsletter\SignupBlock;
use Brain\Monkey\Functions;

describe('SignupBlock', function () {

    beforeEach(function () {
        // Escape and translation stubs shared across tests.
        Functions\when('esc_url')->alias(
            static fn(string $url): string => filter_var($url, FILTER_SANITIZE_URL) ?: $url
        );
        Functions\when('esc_html')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('wp_kses_post')->alias(
            static fn(string $text): string => $text
        );
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_html_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('esc_attr_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('trailingslashit')->alias(
            static fn(string $url): string => rtrim($url, '/') . '/'
        );
        Functions\when('get_block_wrapper_attributes')->alias(
            static function (array $extra = []): string {
                $classes = $extra['class'] ?? '';
                return 'class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '"';
            }
        );
        // Default: no privacy policy URL set.
        Functions\when('get_privacy_policy_url')->justReturn('');

        // Register add_action so init() doesn't trigger Brain Monkey complaints.
        Functions\when('add_action')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return true;
            }
        );

        // register_block_type — we test it is called; a no-op stub is fine for unit tests.
        Functions\when('register_block_type')->justReturn(null);
    });

    // -----------------------------------------------------------------------
    // init()
    // -----------------------------------------------------------------------

    describe('init()', function () {

        it('registers the init action hook', function () {
            $captured = [];

            // Override the beforeEach alias to capture calls.
            Functions\when('add_action')->alias(
                static function (string $hook, mixed $callback, int $priority = 10) use (&$captured): bool {
                    $captured[] = $hook;
                    return true;
                }
            );

            $block = new SignupBlock();
            $block->init();

            expect($captured)->toContain('init');
        });
    });

    // -----------------------------------------------------------------------
    // register_block()
    // -----------------------------------------------------------------------

    describe('register_block()', function () {

        it('calls register_block_type with the correct block slug', function () {
            $registered = null;
            Functions\when('register_block_type')->alias(
                static function (string $name, array $args) use (&$registered): void {
                    $registered = $name;
                }
            );

            $block = new SignupBlock();
            $block->register_block();

            expect($registered)->toBe('crispytheme/newsletter-signup');
        });

        it('sets a render_callback pointing to render_block', function () {
            $capturedArgs = [];
            Functions\when('register_block_type')->alias(
                static function (string $name, array $args) use (&$capturedArgs): void {
                    $capturedArgs = $args;
                }
            );

            $block = new SignupBlock();
            $block->register_block();

            expect($capturedArgs)->toHaveKey('render_callback');
            expect($capturedArgs['render_callback'])->toBe([$block, 'render_block']);
        });

        it('includes the substackUrl attribute with a default value', function () {
            $capturedArgs = [];
            Functions\when('register_block_type')->alias(
                static function (string $name, array $args) use (&$capturedArgs): void {
                    $capturedArgs = $args;
                }
            );

            $block = new SignupBlock();
            $block->register_block();

            expect($capturedArgs['attributes']['substackUrl']['default'])
                ->toBe('https://almosttimely.substack.com');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — default attributes
    // -----------------------------------------------------------------------

    describe('render_block() with default attributes', function () {

        it('returns a non-empty string', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->toBeString()->not->toBeEmpty();
        });

        it('uses the newsletter-signup CSS class by default (hero style)', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->toContain('newsletter-signup');
        });

        it('embeds the Substack embed URL in the iframe src', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->toContain('almosttimely.substack.com');
            expect($output)->toContain('embed');
        });

        it('does not render the heading when no heading is set', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->not->toContain('newsletter-heading');
        });

        it('does not render the description when no description is set', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->not->toContain('newsletter-description');
        });

        it('does not show subscriber count by default', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->not->toContain('newsletter-subscriber-count');
        });

        it('renders the newsletter-privacy paragraph', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->toContain('newsletter-privacy');
            expect($output)->toContain('Your privacy is protected');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — inline style
    // -----------------------------------------------------------------------

    describe('render_block() with inline style', function () {

        it('uses the newsletter-inline CSS class for inline style', function () {
            $block  = new SignupBlock();
            $output = $block->render_block(['style' => 'inline']);

            expect($output)->toContain('newsletter-inline');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — heading and description
    // -----------------------------------------------------------------------

    describe('render_block() with heading and description', function () {

        it('renders the heading when provided', function () {
            $block  = new SignupBlock();
            $output = $block->render_block(['heading' => 'Join the Newsletter']);

            expect($output)->toContain('newsletter-heading');
            expect($output)->toContain('Join the Newsletter');
        });

        it('renders the description when provided', function () {
            $block  = new SignupBlock();
            $output = $block->render_block(['description' => 'Get the latest AI insights']);

            expect($output)->toContain('newsletter-description');
            expect($output)->toContain('Get the latest AI insights');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — subscriber count
    // -----------------------------------------------------------------------

    describe('render_block() with subscriber count enabled', function () {

        it('shows subscriber count when showSubscriberCount is true', function () {
            $block  = new SignupBlock();
            $output = $block->render_block([
                'showSubscriberCount' => true,
                'subscriberCount'     => '294,000+',
            ]);

            expect($output)->toContain('newsletter-subscriber-count');
            expect($output)->toContain('294,000+');
            expect($output)->toContain('newsletter-subscriber-label');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — custom Substack URL
    // -----------------------------------------------------------------------

    describe('render_block() with a custom substackUrl', function () {

        it('uses the custom Substack URL for the iframe embed', function () {
            $block  = new SignupBlock();
            $output = $block->render_block(['substackUrl' => 'https://mycustom.substack.com']);

            expect($output)->toContain('mycustom.substack.com');
        });
    });

    // -----------------------------------------------------------------------
    // render_block() — privacy policy URL present
    // -----------------------------------------------------------------------

    describe('render_block() when a privacy policy URL is configured', function () {

        it('renders a Privacy Policy link when the URL is set', function () {
            Functions\when('get_privacy_policy_url')->justReturn('https://example.com/privacy/');

            $block  = new SignupBlock();
            $output = $block->render_block([]);

            expect($output)->toContain('Privacy Policy');
            expect($output)->toContain('https://example.com/privacy/');
        });
    });
});
