<?php
/**
 * Editor Unit Tests
 *
 * Tests the Admin\Editor class which disables the block editor
 * and provides markdown-editor-oriented admin UI.
 *
 * @package CrispyTheme\Tests\Unit\Admin
 */

declare(strict_types=1);

use CrispyTheme\Admin\Editor;
use Brain\Monkey\Functions;

// WP_Post stub — only created here if not already defined.
if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID               = 1;
        public string $post_content  = '';
        public string $post_excerpt  = '';
        public string $post_type     = 'post';
        public string $post_status   = 'publish';
        public string $post_title    = '';

        public function __construct(array $attrs = [])
        {
            foreach ($attrs as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

// ------------------------------------------------------------------
// Shared helper: stub common WP output functions for this test file.
// We do this once globally rather than per-test, because Brain Monkey
// allows stubs registered at test-setup time. However stubs() can only
// be called once per function name per Brain Monkey cycle, so we use
// when() in beforeEach for per-test re-stubs.
// ------------------------------------------------------------------

describe('Editor', function () {

    beforeEach(function () {
        Functions\when('esc_html')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_url')->alias(
            static fn(string $url): string => filter_var($url, FILTER_SANITIZE_URL) ?: $url
        );
        Functions\when('esc_js')->alias(
            static fn(string $text): string => addslashes($text)
        );
        Functions\when('esc_html__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_html_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('wp_kses')->alias(
            static fn(string $data, mixed $allowed): string => $data
        );
    });

    // ------------------------------------------------------------------
    // init() — hook registrations
    // ------------------------------------------------------------------

    describe('init()', function () {
        it('registers block editor disable filters', function () {
            $filterHooks = [];
            Functions\when('add_filter')->alias(
                function (string $hook, mixed $callback) use (&$filterHooks): bool {
                    $filterHooks[] = $hook;
                    return true;
                }
            );
            Functions\when('add_action')->justReturn(true);

            (new Editor())->init();

            expect($filterHooks)->toContain('use_block_editor_for_post');
            expect($filterHooks)->toContain('use_block_editor_for_post_type');
            expect($filterHooks)->toContain('use_widgets_block_editor');
        });

        it('registers admin_notices action for markdown notice', function () {
            $actionHooks = [];
            Functions\when('add_filter')->justReturn(true);
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$actionHooks): bool {
                    $actionHooks[] = $hook;
                    return true;
                }
            );

            (new Editor())->init();

            expect($actionHooks)->toContain('admin_notices');
        });

        it('registers page_row_actions and post_row_actions filters', function () {
            $filterHooks = [];
            Functions\when('add_action')->justReturn(true);
            Functions\when('add_filter')->alias(
                function (string $hook, mixed $callback) use (&$filterHooks): bool {
                    $filterHooks[] = $hook;
                    return true;
                }
            );

            (new Editor())->init();

            expect($filterHooks)->toContain('page_row_actions');
            expect($filterHooks)->toContain('post_row_actions');
        });

        it('registers admin_head action for hide_default_editor', function () {
            $actionHooks = [];
            Functions\when('add_filter')->justReturn(true);
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$actionHooks): bool {
                    $actionHooks[] = $hook;
                    return true;
                }
            );

            (new Editor())->init();

            expect($actionHooks)->toContain('admin_head');
        });
    });

    // ------------------------------------------------------------------
    // maybe_remove_block_styles()
    // ------------------------------------------------------------------

    describe('maybe_remove_block_styles()', function () {
        it('does nothing when filter returns false', function () {
            Functions\when('apply_filters')->alias(
                static fn(string $hook, mixed $val): mixed => $val
            );
            // If wp_dequeue_style is called, Brain Monkey will blow up with
            // an unexpected call error — so simply not registering it acts as
            // a guard. We use justReturn to allow the call path to proceed.
            // But we don't expect it to be reached on the false-return path.
            // To confirm it is NOT called we capture calls.
            $called = false;
            Functions\when('wp_dequeue_style')->alias(
                function () use (&$called): void {
                    $called = true;
                }
            );

            (new Editor())->maybe_remove_block_styles();

            expect($called)->toBeFalse();
        });

        it('dequeues block styles when filter returns true', function () {
            Functions\when('apply_filters')->alias(
                static fn(string $hook, mixed $val): mixed => true
            );

            $dequeued = [];
            Functions\when('wp_dequeue_style')->alias(
                function (string $handle) use (&$dequeued): void {
                    $dequeued[] = $handle;
                }
            );

            (new Editor())->maybe_remove_block_styles();

            expect($dequeued)->toContain('wp-block-library');
            expect($dequeued)->toContain('wp-block-library-theme');
            expect($dequeued)->toContain('wc-blocks-style');
        });
    });

    // ------------------------------------------------------------------
    // show_markdown_notice()
    // ------------------------------------------------------------------

    describe('show_markdown_notice()', function () {
        it('outputs nothing when screen is null', function () {
            Functions\when('get_current_screen')->justReturn(null);

            ob_start();
            (new Editor())->show_markdown_notice();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs nothing on non-post screens', function () {
            $screen = new class { public string $base = 'dashboard'; };
            Functions\when('get_current_screen')->justReturn($screen);

            ob_start();
            (new Editor())->show_markdown_notice();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs nothing when notice is already dismissed', function () {
            $screen = new class { public string $base = 'post'; };
            Functions\when('get_current_screen')->justReturn($screen);
            Functions\when('get_current_user_id')->justReturn(1);
            Functions\when('get_user_meta')->justReturn('1');

            ob_start();
            (new Editor())->show_markdown_notice();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs notice on post screen when not dismissed', function () {
            $screen = new class { public string $base = 'post'; };
            Functions\when('get_current_screen')->justReturn($screen);
            Functions\when('get_current_user_id')->justReturn(1);
            Functions\when('get_user_meta')->justReturn('');
            Functions\when('wp_create_nonce')->justReturn('test_nonce_value');

            ob_start();
            (new Editor())->show_markdown_notice();
            $output = ob_get_clean();

            expect($output)->toContain('notice-info');
            expect($output)->toContain('CrispyTheme Markdown Editor');
        });

        it('outputs notice on post-new screen when not dismissed', function () {
            $screen = new class { public string $base = 'post-new'; };
            Functions\when('get_current_screen')->justReturn($screen);
            Functions\when('get_current_user_id')->justReturn(2);
            Functions\when('get_user_meta')->justReturn('');
            Functions\when('wp_create_nonce')->justReturn('test_nonce_value');

            ob_start();
            (new Editor())->show_markdown_notice();
            $output = ob_get_clean();

            expect($output)->toContain('notice');
        });
    });

    // ------------------------------------------------------------------
    // hide_default_editor()
    // ------------------------------------------------------------------

    describe('hide_default_editor()', function () {
        it('outputs nothing when screen is null', function () {
            Functions\when('get_current_screen')->justReturn(null);

            ob_start();
            (new Editor())->hide_default_editor();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs nothing on non-post screen', function () {
            $screen = new class { public string $base = 'options'; };
            Functions\when('get_current_screen')->justReturn($screen);

            ob_start();
            (new Editor())->hide_default_editor();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs style block on post screen', function () {
            $screen = new class { public string $base = 'post'; };
            Functions\when('get_current_screen')->justReturn($screen);

            ob_start();
            (new Editor())->hide_default_editor();
            $output = ob_get_clean();

            expect($output)->toContain('<style>');
            expect($output)->toContain('#postdivrich');
        });
    });

    // ------------------------------------------------------------------
    // show_site_editor_notice()
    // ------------------------------------------------------------------

    describe('show_site_editor_notice()', function () {
        it('outputs nothing when screen is null', function () {
            Functions\when('get_current_screen')->justReturn(null);

            ob_start();
            (new Editor())->show_site_editor_notice();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs nothing on non-site-editor screen', function () {
            $screen = new class { public string $base = 'post'; };
            Functions\when('get_current_screen')->justReturn($screen);

            ob_start();
            (new Editor())->show_site_editor_notice();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('outputs notice on site-editor screen with links', function () {
            $screen = new class { public string $base = 'site-editor'; };
            Functions\when('get_current_screen')->justReturn($screen);
            Functions\when('admin_url')->alias(
                static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
            );

            ob_start();
            (new Editor())->show_site_editor_notice();
            $output = ob_get_clean();

            expect($output)->toContain('notice-info');
            expect($output)->toContain('CrispyTheme Markdown Editor');
        });
    });

    // ------------------------------------------------------------------
    // add_edit_markdown_link()
    // ------------------------------------------------------------------

    describe('add_edit_markdown_link()', function () {
        it('returns unchanged actions when user cannot edit post', function () {
            Functions\when('current_user_can')->justReturn(false);

            $post    = new WP_Post(['ID' => 1, 'post_title' => 'Hello']);
            $actions = ['edit' => '<a href="#">Edit</a>', 'trash' => '<a href="#">Trash</a>'];

            $editor = new Editor();
            $result = $editor->add_edit_markdown_link($actions, $post);

            expect($result)->toBe($actions);
        });

        it('replaces edit action with Edit Markdown link when user can edit', function () {
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('admin_url')->alias(
                static fn(string $path): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
            );

            $post    = new WP_Post(['ID' => 42, 'post_title' => 'Test Post']);
            $actions = ['edit' => '<a href="#">Edit</a>'];

            $editor = new Editor();
            $result = $editor->add_edit_markdown_link($actions, $post);

            expect($result['edit'])->toContain('Edit Markdown');
            expect($result['edit'])->toContain('post=42');
        });

        it('replaces edit action using (no title) for empty post title', function () {
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('admin_url')->alias(
                static fn(string $path): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
            );

            $post    = new WP_Post(['ID' => 5, 'post_title' => '']);
            $actions = ['edit' => '<a href="#">Edit</a>'];

            $editor = new Editor();
            $result = $editor->add_edit_markdown_link($actions, $post);

            expect($result['edit'])->toContain('Edit Markdown');
        });

        it('does not change actions when no edit key present', function () {
            Functions\when('current_user_can')->justReturn(true);

            $post    = new WP_Post(['ID' => 1, 'post_title' => 'Hello']);
            $actions = ['trash' => '<a href="#">Trash</a>'];

            $editor = new Editor();
            $result = $editor->add_edit_markdown_link($actions, $post);

            expect($result)->not->toHaveKey('edit');
            expect($result['trash'])->toBe('<a href="#">Trash</a>');
        });
    });

    // ------------------------------------------------------------------
    // handle_notice_dismissal() — static registration
    // ------------------------------------------------------------------

    describe('handle_notice_dismissal()', function () {
        it('registers AJAX action for dismissal', function () {
            $registered = [];
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$registered): bool {
                    $registered[] = $hook;
                    return true;
                }
            );

            Editor::handle_notice_dismissal();

            expect($registered)->toContain('wp_ajax_crispytheme_dismiss_notice');
        });
    });

    // ------------------------------------------------------------------
    // dismiss_notice() — static handler
    // ------------------------------------------------------------------

    describe('dismiss_notice()', function () {
        afterEach(function () {
            unset($_POST['notice']);
        });

        it('updates user meta and sends success for markdown-editor notice', function () {
            $_POST['notice'] = 'markdown-editor';

            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('sanitize_key')->alias(static fn(string $v): string => $v);
            Functions\when('get_current_user_id')->justReturn(10);

            $updated = false;
            Functions\when('update_user_meta')->alias(
                function (int $uid, string $key, mixed $val) use (&$updated): bool {
                    $updated = true;
                    return true;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            Editor::dismiss_notice();

            expect($updated)->toBeTrue();
        });

        it('sends success without updating meta for unknown notice', function () {
            $_POST['notice'] = 'unknown-notice';

            Functions\when('check_ajax_referer')->justReturn(true);
            Functions\when('sanitize_key')->alias(static fn(string $v): string => $v);
            Functions\when('get_current_user_id')->justReturn(10);

            // update_user_meta should NOT be called; capture any call to verify
            $updateCalled = false;
            Functions\when('update_user_meta')->alias(
                function () use (&$updateCalled): bool {
                    $updateCalled = true;
                    return true;
                }
            );
            Functions\when('wp_send_json_success')->alias(static function (): void {});

            Editor::dismiss_notice();

            expect($updateCalled)->toBeFalse();
        });
    });
});
