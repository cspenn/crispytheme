<?php
/**
 * MetaBox Unit Tests
 *
 * Tests the Admin\MetaBox class which registers and renders the Markdown
 * content metabox and handles save_post.
 *
 * @package CrispyTheme\Tests\Unit\Admin
 */

declare(strict_types=1);

use CrispyTheme\Admin\MetaBox;
use CrispyTheme\Content\MarkdownRenderer;
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

describe('MetaBox', function () {

    beforeEach(function () {
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_html__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_html')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('esc_textarea')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
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
    });

    // ------------------------------------------------------------------
    // Static accessor
    // ------------------------------------------------------------------

    describe('get_meta_box_id()', function () {
        it('returns the correct metabox ID', function () {
            expect(MetaBox::get_meta_box_id())->toBe('markdown_editor');
        });
    });

    // ------------------------------------------------------------------
    // init()
    // ------------------------------------------------------------------

    describe('init()', function () {
        it('registers add_meta_boxes and save_post hooks', function () {
            $registered = [];
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$registered): bool {
                    $registered[] = $hook;
                    return true;
                }
            );

            (new MetaBox())->init();

            expect($registered)->toContain('add_meta_boxes');
            expect($registered)->toContain('save_post');
        });
    });

    // ------------------------------------------------------------------
    // register_meta_box()
    // ------------------------------------------------------------------

    describe('register_meta_box()', function () {
        it('calls add_meta_box for post types that support editor', function () {
            Functions\when('get_post_types')->justReturn(['post', 'page', 'attachment']);
            Functions\when('post_type_supports')->alias(
                static fn(string $type, string $feature): bool => in_array($type, ['post', 'page'], true)
            );

            $added = [];
            Functions\when('add_meta_box')->alias(
                function (string $id, string $title, mixed $cb, string $postType) use (&$added): void {
                    $added[] = $postType;
                }
            );

            (new MetaBox())->register_meta_box();

            expect($added)->toContain('post');
            expect($added)->toContain('page');
            expect($added)->not->toContain('attachment');
        });

        it('skips all post types when none support editor', function () {
            Functions\when('get_post_types')->justReturn(['portfolio']);
            Functions\when('post_type_supports')->justReturn(false);

            $called = false;
            Functions\when('add_meta_box')->alias(function () use (&$called): void {
                $called = true;
            });

            (new MetaBox())->register_meta_box();

            expect($called)->toBeFalse();
        });
    });

    // ------------------------------------------------------------------
    // render_meta_box()
    // ------------------------------------------------------------------

    describe('render_meta_box()', function () {
        it('renders nonce field and textarea with existing markdown', function () {
            $post = new WP_Post([
                'ID'           => 1,
                'post_content' => '',
                'post_title'   => 'Test Post',
            ]);

            Functions\when('get_post_meta')->justReturn('# Existing Markdown');
            Functions\when('wp_nonce_field')->alias(
                static fn(string $action, string $name): string => "<input type=\"hidden\" name=\"{$name}\" value=\"nonce_value\">"
            );
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');

            ob_start();
            (new MetaBox())->render_meta_box($post);
            $output = ob_get_clean();

            expect($output)->toContain('crispy-markdown-editor');
            expect($output)->toContain('crispy_markdown_content');
            expect($output)->toContain('# Existing Markdown');
        });

        it('shows auto-converted notice when post has HTML but no markdown', function () {
            $post = new WP_Post([
                'ID'           => 2,
                'post_content' => '<h1>Hello World</h1>',
                'post_title'   => 'Test',
            ]);

            // No markdown stored yet.
            Functions\when('get_post_meta')->justReturn('');
            Functions\when('wp_nonce_field')->alias(
                static fn(string $action, string $name): string => "<input type=\"hidden\" name=\"{$name}\" value=\"nonce_value\">"
            );
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');

            ob_start();
            (new MetaBox())->render_meta_box($post);
            $output = ob_get_clean();

            expect($output)->toContain('Content Auto-Converted');
        });

        it('shows re-convert button when post has both markdown and HTML content', function () {
            $post = new WP_Post([
                'ID'           => 3,
                'post_content' => '<p>Some HTML</p>',
                'post_title'   => 'Test',
            ]);

            Functions\when('get_post_meta')->justReturn('# Some Markdown already here');
            Functions\when('wp_nonce_field')->alias(
                static fn(string $action, string $name): string => "<input type=\"hidden\" name=\"{$name}\" value=\"nonce_value\">"
            );
            Functions\when('apply_filters')->returnArg(2);
            Functions\when('get_option')->justReturn('extra');

            ob_start();
            (new MetaBox())->render_meta_box($post);
            $output = ob_get_clean();

            expect($output)->toContain('crispy-reconvert-btn');
            expect($output)->toContain('Re-convert from HTML');
        });

        it('renders placeholder preview when no markdown content exists', function () {
            $post = new WP_Post([
                'ID'           => 4,
                'post_content' => '',
                'post_title'   => '',
            ]);

            Functions\when('get_post_meta')->justReturn('');
            Functions\when('wp_nonce_field')->alias(
                static fn(string $action, string $name): string => "<input type=\"hidden\" name=\"{$name}\" value=\"nonce_value\">"
            );

            ob_start();
            (new MetaBox())->render_meta_box($post);
            $output = ob_get_clean();

            expect($output)->toContain('crispy-markdown-editor__placeholder');
            expect($output)->toContain('Nothing to preview');
        });
    });

    // ------------------------------------------------------------------
    // save_markdown_content()
    // ------------------------------------------------------------------

    describe('save_markdown_content()', function () {
        afterEach(function () {
            unset($_POST['crispytheme_markdown_nonce'], $_POST['crispy_markdown_content']);
        });

        it('does nothing when nonce field is absent', function () {
            unset($_POST['crispytheme_markdown_nonce']);

            // Verify we don't reach wp_verify_nonce.
            $verifyCalled = false;
            Functions\when('wp_verify_nonce')->alias(function () use (&$verifyCalled): bool {
                $verifyCalled = true;
                return false;
            });
            $updateCalled = false;
            Functions\when('update_post_meta')->alias(function () use (&$updateCalled): bool {
                $updateCalled = true;
                return true;
            });

            $post = new WP_Post(['ID' => 1]);
            (new MetaBox())->save_markdown_content(1, $post);

            expect($verifyCalled)->toBeFalse();
            expect($updateCalled)->toBeFalse();
        });

        it('does nothing when nonce is invalid', function () {
            $_POST['crispytheme_markdown_nonce'] = 'bad_nonce';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(false);

            $updateCalled = false;
            Functions\when('update_post_meta')->alias(function () use (&$updateCalled): bool {
                $updateCalled = true;
                return true;
            });

            $post = new WP_Post(['ID' => 1]);
            (new MetaBox())->save_markdown_content(1, $post);

            expect($updateCalled)->toBeFalse();
        });
    });

    // Tests for save paths that require valid nonce + permissions.
    // Note: DOING_AUTOSAVE is a PHP constant — once defined as true in a process it
    // cannot be redefined. The autosave guard test lives in a separate describe block
    // AFTER this one, so these real-path tests run first while DOING_AUTOSAVE is still
    // undefined. The subsequent `save_markdown_content() autosave guard` block defines
    // it and must remain last.
    describe('save_markdown_content() permission and content branches', function () {
        afterEach(function () {
            unset($_POST['crispytheme_markdown_nonce'], $_POST['crispy_markdown_content']);
        });

        it('does nothing when user cannot edit post', function () {
            // Only run this test when DOING_AUTOSAVE is not blocking us.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                expect(true)->toBeTrue(); // Guard: skip.
                return;
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);
            Functions\when('current_user_can')->justReturn(false);

            $updateCalled = false;
            Functions\when('update_post_meta')->alias(function () use (&$updateCalled): bool {
                $updateCalled = true;
                return true;
            });

            $post = new WP_Post(['ID' => 1]);
            (new MetaBox())->save_markdown_content(1, $post);

            expect($updateCalled)->toBeFalse();
        });

        it('does nothing for post revisions', function () {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                expect(true)->toBeTrue();
                return;
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_is_post_revision')->justReturn(99);

            $updateCalled = false;
            Functions\when('update_post_meta')->alias(function () use (&$updateCalled): bool {
                $updateCalled = true;
                return true;
            });

            $post = new WP_Post(['ID' => 1]);
            (new MetaBox())->save_markdown_content(1, $post);

            expect($updateCalled)->toBeFalse();
        });

        it('updates post meta with provided markdown', function () {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                expect(true)->toBeTrue();
                return;
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';
            $_POST['crispy_markdown_content']     = '# My Markdown';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_is_post_revision')->justReturn(false);

            $updatedKey = null;
            $updatedVal = null;
            Functions\when('update_post_meta')->alias(
                function (int $id, string $key, mixed $val) use (&$updatedKey, &$updatedVal): bool {
                    $updatedKey = $key;
                    $updatedVal = $val;
                    return true;
                }
            );

            $post = new WP_Post(['ID' => 5]);
            (new MetaBox())->save_markdown_content(5, $post);

            expect($updatedKey)->toBe(MarkdownRenderer::META_KEY);
            expect($updatedVal)->toBe('# My Markdown');
        });

        it('deletes post meta when markdown content is empty', function () {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                expect(true)->toBeTrue();
                return;
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';
            $_POST['crispy_markdown_content']     = '';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_is_post_revision')->justReturn(false);

            $deletedKey = null;
            Functions\when('delete_post_meta')->alias(
                function (int $id, string $key) use (&$deletedKey): bool {
                    $deletedKey = $key;
                    return true;
                }
            );

            $post = new WP_Post(['ID' => 6]);
            (new MetaBox())->save_markdown_content(6, $post);

            expect($deletedKey)->toBe(MarkdownRenderer::META_KEY);
        });

        it('treats absent crispy_markdown_content POST key as empty string', function () {
            // Covers the `: ''` fallback on line 346 of MetaBox.php.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                expect(true)->toBeTrue();
                return;
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';
            unset($_POST['crispy_markdown_content']); // Key absent → $markdown = ''

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('wp_is_post_revision')->justReturn(false);

            $deletedKey = null;
            Functions\when('delete_post_meta')->alias(
                function (int $id, string $key) use (&$deletedKey): bool {
                    $deletedKey = $key;
                    return true;
                }
            );

            $post = new WP_Post(['ID' => 7]);
            (new MetaBox())->save_markdown_content(7, $post);

            // When $markdown is empty, meta is deleted (same as empty string case).
            expect($deletedKey)->toBe(MarkdownRenderer::META_KEY);
        });
    });

    // ------------------------------------------------------------------
    // Autosave guard — must be the LAST save describe block.
    // Defines DOING_AUTOSAVE=true (a PHP constant, immutable once set), which
    // would cause all subsequent guarded tests to take the trivial skip path.
    // By placing it last, all real-path tests above run unaffected first.
    // ------------------------------------------------------------------

    describe('save_markdown_content() autosave guard', function () {
        afterEach(function () {
            unset($_POST['crispytheme_markdown_nonce'], $_POST['crispy_markdown_content']);
        });

        it('returns early during autosave without touching post meta', function () {
            // Guard: only define if not yet set (safe for re-runs or unexpected ordering).
            if (!defined('DOING_AUTOSAVE')) {
                define('DOING_AUTOSAVE', true);
            }

            $_POST['crispytheme_markdown_nonce'] = 'valid_nonce';

            Functions\when('wp_unslash')->alias(static fn(mixed $v): mixed => $v);
            Functions\when('wp_verify_nonce')->justReturn(true);

            $updateCalled = false;
            Functions\when('update_post_meta')->alias(function () use (&$updateCalled): bool {
                $updateCalled = true;
                return true;
            });
            $deleteCalled = false;
            Functions\when('delete_post_meta')->alias(function () use (&$deleteCalled): bool {
                $deleteCalled = true;
                return true;
            });

            $post = new WP_Post(['ID' => 2]);
            (new MetaBox())->save_markdown_content(2, $post);

            // DOING_AUTOSAVE causes an early return — neither update nor delete fires.
            expect($updateCalled)->toBeFalse();
            expect($deleteCalled)->toBeFalse();
        });
    });
});
