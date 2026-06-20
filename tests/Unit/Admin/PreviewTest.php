<?php
/**
 * Preview Unit Tests
 *
 * Tests the Admin\Preview class which handles AJAX preview and re-convert
 * requests for the markdown editor.
 *
 * NOTE on wp_send_json_error/success: In real WordPress these call wp_die()
 * which exits the request. In tests the mock just records the call and returns,
 * so execution continues after the JSON function. Tests must account for this
 * by verifying the FIRST call — not that execution stops.
 *
 * @package CrispyTheme\Tests\Unit\Admin
 */

declare(strict_types=1);

use CrispyTheme\Admin\Preview;
use Brain\Monkey\Functions;

// WP_Post stub.
if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID               = 1;
        public string $post_content  = '';
        public string $post_title    = '';
        public string $post_type     = 'post';
        public string $post_status   = 'publish';

        public function __construct(array $attrs = [])
        {
            foreach ($attrs as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

describe('Preview', function () {

    beforeEach(function () {
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_html__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
    });

    // ------------------------------------------------------------------
    // Static accessors
    // ------------------------------------------------------------------

    describe('get_ajax_action()', function () {
        it('returns the correct AJAX action string', function () {
            expect(Preview::get_ajax_action())->toBe('crispytheme_preview_markdown');
        });
    });

    // ------------------------------------------------------------------
    // init() — hook registrations
    // ------------------------------------------------------------------

    describe('init()', function () {
        it('registers AJAX actions on init', function () {
            $registered = [];
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$registered): bool {
                    $registered[] = $hook;
                    return true;
                }
            );

            (new Preview())->init();

            expect($registered)->toContain('wp_ajax_crispytheme_preview_markdown');
            expect($registered)->toContain('wp_ajax_crispytheme_reconvert_markdown');
            expect($registered)->toContain('wp_ajax_crispytheme_debug_test');
        });
    });

    // ------------------------------------------------------------------
    // handle_debug_test()
    // ------------------------------------------------------------------

    describe('handle_debug_test()', function () {
        it('sends JSON success with message, timestamp, and php version', function () {
            // gmdate is a PHP native — let it run. Don't mock it.
            $sent = null;
            Functions\when('wp_send_json_success')->alias(
                function (mixed $data) use (&$sent): void {
                    $sent = $data;
                }
            );

            (new Preview())->handle_debug_test();

            expect($sent)->toBeArray();
            expect($sent)->toHaveKey('message');
            expect($sent)->toHaveKey('timestamp');
            expect($sent)->toHaveKey('php');
            expect($sent['message'])->toContain('AJAX is working');
        });
    });

    // ------------------------------------------------------------------
    // handle_preview_request()
    // ------------------------------------------------------------------

    describe('handle_preview_request()', function () {
        afterEach(function () {
            unset($_POST['markdown']);
        });

        it('calls wp_send_json_error with 403 when nonce check fails', function () {
            Functions\when('check_ajax_referer')->justReturn(false);
            // After json_error is called, execution continues and current_user_can will be hit
            // (since there's no exit). Stub it to prevent undefined-function error.
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = ['data' => $data, 'status' => $status];
                }
            );
            // The successful path tries to render — stub minimally to prevent further errors.
            Functions\when('wp_send_json_success')->alias(static function (): void {});
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');

            $_POST['markdown'] = '';

            (new Preview())->handle_preview_request();

            // First error should be the nonce failure (403).
            expect($errors)->not->toBeEmpty();
            expect($errors[0]['status'])->toBe(403);
        });

        it('calls wp_send_json_error with 403 when user lacks permissions', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(false);
            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = ['data' => $data, 'status' => $status];
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');

            $_POST['markdown'] = '';

            (new Preview())->handle_preview_request();

            expect($errors)->not->toBeEmpty();
            expect($errors[0]['status'])->toBe(403);
        });

        it('sends empty preview when markdown is empty and user can edit', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_send_json_error')->alias(static function (): void {});

            $_POST['markdown'] = '';

            $sent = null;
            Functions\when('wp_send_json_success')->alias(
                function (mixed $data) use (&$sent): void {
                    if ($sent === null) {
                        $sent = $data;
                    }
                }
            );

            (new Preview())->handle_preview_request();

            expect($sent)->toBeArray();
            expect($sent)->toHaveKey('word_count');
            expect($sent['word_count'])->toBe(0);
        });

        it('uses empty string as fallback when markdown key is absent from POST', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            // Do NOT set $_POST['markdown'] — this covers the `: ''` fallback (line 91).
            unset($_POST['markdown']);
            Functions\when('wp_send_json_error')->alias(static function (): void {});

            $sent = null;
            Functions\when('wp_send_json_success')->alias(
                function (mixed $data) use (&$sent): void {
                    if ($sent === null) {
                        $sent = $data;
                    }
                }
            );

            (new Preview())->handle_preview_request();

            // With no markdown key, $markdown = '' → empty preview is returned.
            expect($sent)->toBeArray();
            expect($sent['word_count'])->toBe(0);
        });

        it('sends rendered HTML and word/char counts for valid markdown', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_strip_all_tags')->alias(
                static fn(string $html): string => strip_tags($html)
            );
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');
            Functions\when('wp_send_json_error')->alias(static function (): void {});

            $_POST['markdown'] = '# Hello World';

            $sent = null;
            Functions\when('wp_send_json_success')->alias(
                function (mixed $data) use (&$sent): void {
                    if ($sent === null) {
                        $sent = $data;
                    }
                }
            );

            (new Preview())->handle_preview_request();

            expect($sent)->toBeArray();
            expect($sent)->toHaveKey('html');
            expect($sent)->toHaveKey('word_count');
            expect($sent)->toHaveKey('char_count');
            expect($sent['char_count'])->toBe(13); // mb_strlen('# Hello World')
        });
    });

    // ------------------------------------------------------------------
    // handle_reconvert_request()
    // ------------------------------------------------------------------

    describe('handle_reconvert_request()', function () {
        afterEach(function () {
            unset($_POST['post_id']);
        });

        // Helper: stub common functions for the full reconvert request path.
        // Since wp_send_json_error has no exit in tests, execution always falls through
        // all conditions. We stub all possible branch functions to prevent crashes.
        //
        // We pass post_id=0 for tests that should stop at early checks, which ensures
        // get_post is never called (absint(0) = 0 < 1 triggers the early return).

        // NOTE: since wp_send_json_error has no exit in tests, execution
        // falls through ALL conditions. We must stub get_post (called after
        // post_id check) to prevent "undefined function" crashes, and provide
        // a WP_Post so further code doesn't crash on null->post_content.

        it('calls wp_send_json_error with 403 when nonce check fails', function () {
            Functions\when('check_ajax_referer')->justReturn(false);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));
            // Stub get_post — execution falls through the 403/400 error paths.
            $postStub = new WP_Post(['ID' => 0, 'post_content' => '   ']);
            Functions\when('get_post')->justReturn($postStub);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = $status;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            $_POST['post_id'] = '0';

            (new Preview())->handle_reconvert_request();

            expect($errors)->not->toBeEmpty();
            expect($errors[0])->toBe(403);
        });

        it('calls wp_send_json_error with 403 when user lacks permissions', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(false);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));
            $postStub = new WP_Post(['ID' => 0, 'post_content' => '   ']);
            Functions\when('get_post')->justReturn($postStub);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = $status;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            $_POST['post_id'] = '0';

            (new Preview())->handle_reconvert_request();

            expect($errors)->not->toBeEmpty();
            expect($errors[0])->toBe(403);
        });

        it('calls wp_send_json_error with 400 for invalid zero post_id', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));
            // Execution falls through the 400 check to get_post.
            $postStub = new WP_Post(['ID' => 0, 'post_content' => '   ']);
            Functions\when('get_post')->justReturn($postStub);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = $status;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            $_POST['post_id'] = '0';

            (new Preview())->handle_reconvert_request();

            // After nonce+permission pass, post_id=0 triggers 400 first.
            expect(in_array(400, $errors, true))->toBeTrue();
        });

        it('sends 404 error when post is not found', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));

            // get_post returns null — fails the instanceof WP_Post check → 404 error.
            // Since wp_send_json_error has no exit in tests, execution falls through
            // to trim($post->post_content) on null. We catch the TypeError to verify
            // the 404 was emitted before the crash.
            Functions\when('get_post')->justReturn(null);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = $status;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            $_POST['post_id'] = '99';

            $caughtError = null;
            try {
                (new Preview())->handle_reconvert_request();
            } catch (\TypeError $e) {
                $caughtError = $e;
            }

            // The 404 error must have been recorded before the TypeError crash.
            expect(in_array(404, $errors, true))->toBeTrue();
            // Either the function completed or it threw after the 404 — both are valid.
            expect($caughtError === null || $caughtError instanceof \TypeError)->toBeTrue();
        });

        it('sends 400 error when post has no HTML content', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));

            $post = new WP_Post(['ID' => 1, 'post_content' => '   ']);
            Functions\when('get_post')->justReturn($post);

            $errors = [];
            Functions\when('wp_send_json_error')->alias(
                function (mixed $data, int $status = 200) use (&$errors): void {
                    $errors[] = $status;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            $_POST['post_id'] = '1';

            (new Preview())->handle_reconvert_request();

            expect(in_array(400, $errors, true))->toBeTrue();
        });

        it('sends success with converted markdown for valid post with HTML content', function () {
            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('absint')->alias(static fn(mixed $v): int => abs((int) $v));

            $post = new WP_Post([
                'ID'           => 7,
                'post_content' => '<h1>Hello World</h1><p>Test content.</p>',
            ]);
            Functions\when('get_post')->justReturn($post);
            Functions\when('wp_send_json_error')->alias(static function (): void {});

            $sent = null;
            Functions\when('wp_send_json_success')->alias(
                function (mixed $data) use (&$sent): void {
                    if ($sent === null) {
                        $sent = $data;
                    }
                }
            );

            $_POST['post_id'] = '7';

            (new Preview())->handle_reconvert_request();

            expect($sent)->toBeArray();
            expect($sent)->toHaveKey('markdown');
            expect($sent)->toHaveKey('message');
            expect(strlen($sent['markdown']))->toBeGreaterThan(0);
        });
    });
});
