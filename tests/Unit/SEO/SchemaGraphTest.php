<?php
/**
 * SchemaGraph Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\SchemaGraph;
use Brain\Monkey\Functions;

describe('SchemaGraph', function () {

    beforeEach(function () {
        // Provide a baseline for all WP functions SchemaGraph and its builders use.
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );

        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );

        Functions\when('add_action')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                // Store on test instance so we can inspect if needed.
                if (!isset($this->registeredActions)) {
                    $this->registeredActions = [];
                }
                $this->registeredActions[$hook][] = ['callback' => $callback, 'priority' => $priority];
                return true;
            }
        );

        Functions\when('wp_json_encode')->alias(
            static fn(mixed $data, int $flags = 0): string|false => json_encode($data, $flags)
        );
    });

    // -----------------------------------------------------------------------
    // init()
    // -----------------------------------------------------------------------

    describe('init()', function () {

        it('registers nothing when the CrispySEO plugin owns the schema', function () {
            $captured = [];

            Functions\when('add_action')->alias(
                static function (string $hook) use (&$captured): bool {
                    $captured[] = $hook;
                    return true;
                }
            );

            $graph = new class() extends SchemaGraph {
                protected function plugin_owns_schema(): bool {
                    return true;
                }
            };
            $graph->init();

            expect($captured)->toBe([]);
        });

        it('reads the plugin version constant to decide who owns the schema', function () {
            $graph  = new SchemaGraph();
            $method = new ReflectionMethod($graph, 'plugin_owns_schema');

            expect($method->invoke($graph))->toBe(defined('CRISPY_SEO_VERSION'));
        });

        it('registers a wp_head action with priority 5', function () {
            $captured = [];

            // Override the beforeEach add_action alias to capture calls.
            Functions\when('add_action')->alias(
                static function (string $hook, mixed $callback, int $priority = 10) use (&$captured): bool {
                    $captured[] = ['hook' => $hook, 'priority' => $priority];
                    return true;
                }
            );

            $graph = new SchemaGraph();
            $graph->init();

            $wpHeadHooks = array_filter(
                $captured,
                static fn(array $c): bool => $c['hook'] === 'wp_head'
            );
            expect($wpHeadHooks)->not->toBeEmpty();

            $first = array_values($wpHeadHooks)[0];
            expect($first['priority'])->toBe(5);
        });
    });

    // -----------------------------------------------------------------------
    // render() – admin guard
    // -----------------------------------------------------------------------

    describe('render() in admin context', function () {

        it('outputs nothing when is_admin() returns true', function () {
            Functions\when('is_admin')->justReturn(true);

            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });
    });

    // -----------------------------------------------------------------------
    // render() – front-end, not front page
    // -----------------------------------------------------------------------

    describe('render() on a non-front-page front-end view', function () {

        beforeEach(function () {
            Functions\when('is_admin')->justReturn(false);
            Functions\when('is_front_page')->justReturn(false);
        });

        it('outputs Person and Organization JSON-LD blocks', function () {
            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            expect($output)->toContain('application/ld+json');
            expect($output)->toContain('"@type":"Person"');
            expect($output)->toContain('"@type":"Organization"');
        });

        it('does NOT output VideoObject or Speakable on non-front-page', function () {
            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            expect($output)->not->toContain('"@type":"VideoObject"');
            expect($output)->not->toContain('"@type":"SpeakableSpecification"');
        });
    });

    // -----------------------------------------------------------------------
    // render() – front page
    // -----------------------------------------------------------------------

    describe('render() on the front page', function () {

        beforeEach(function () {
            Functions\when('is_admin')->justReturn(false);
            Functions\when('is_front_page')->justReturn(true);

            // SpeakableSchema calls get_current_url() which uses add_query_arg.
            Functions\when('add_query_arg')->alias(
                static fn(array $args = [], string $url = ''): string => $url
            );

            // VideoObjectSchema requires a video URL to return non-null.
            Functions\when('get_option')->alias(
                static function (string $key, mixed $default = false): mixed {
                    return match ($key) {
                        'crispytheme_schema_video_url'  => 'https://vimeo.com/demo',
                        'crispytheme_schema_video_name' => 'Demo Reel',
                        default                         => $default,
                    };
                }
            );
        });

        it('also outputs VideoObject and Speakable blocks on the front page', function () {
            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            expect($output)->toContain('"@type":"VideoObject"');
            expect($output)->toContain('"@type":"SpeakableSpecification"');
        });

        it('still includes Person and Organization on the front page', function () {
            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            expect($output)->toContain('"@type":"Person"');
            expect($output)->toContain('"@type":"Organization"');
        });
    });

    // -----------------------------------------------------------------------
    // render() – builder returns null (null === $data continue branch, line 99)
    // -----------------------------------------------------------------------

    describe('render() skips a builder that returns null', function () {

        it('skips Person block when person name is empty string', function () {
            Functions\when('is_admin')->justReturn(false);
            Functions\when('is_front_page')->justReturn(false);

            // Force PersonSchema::build() to return null by making the name empty.
            Functions\when('get_option')->alias(
                static function (string $key, mixed $default = false): mixed {
                    return match ($key) {
                        'crispytheme_schema_person_name' => '',   // null return from PersonSchema
                        default                          => $default,
                    };
                }
            );

            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            // Person block is skipped; Organization block still appears.
            expect($output)->not->toContain('"@type":"Person"');
            expect($output)->toContain('"@type":"Organization"');
        });
    });

    // -----------------------------------------------------------------------
    // render() – wp_json_encode returns false (coverage of skip branch)
    // -----------------------------------------------------------------------

    describe('render() skips block when wp_json_encode returns false', function () {

        it('emits no output for a block when wp_json_encode returns false', function () {
            Functions\when('is_admin')->justReturn(false);
            Functions\when('is_front_page')->justReturn(false);

            // Make wp_json_encode always return false to exercise the skip branch.
            Functions\when('wp_json_encode')->justReturn(false);

            $graph = new SchemaGraph();

            ob_start();
            $graph->render();
            $output = ob_get_clean();

            // All blocks skipped — output is empty.
            expect($output)->toBe('');
        });
    });
});
