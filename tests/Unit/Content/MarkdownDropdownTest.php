<?php
/**
 * Markdown Dropdown Tests
 *
 * Comprehensive coverage for MarkdownDropdown: prepend_dropdown, AJAX handlers,
 * raw request handling, shortcode expansion, tracking, and analytics.
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\MarkdownDropdown;
use CrispyTheme\Content\MarkdownRenderer;
use Brain\Monkey\Functions;

// ---------------------------------------------------------------------------
// Minimal WP_Post stub — guard prevents double-declaration when running full suite.
// ---------------------------------------------------------------------------

if (! class_exists('WP_Post')) {
    /**
     * Minimal WP_Post stub for unit testing.
     * Uses untyped properties to accept any value assigned dynamically.
     */
    class WP_Post
    {
        public $ID             = 1;
        public $post_content   = '';
        public $post_excerpt   = '';
        public $post_type      = 'post';
        public $post_status    = 'publish';
        public $post_title     = '';
        public $post_name      = '';
        public $post_author    = '1';

        public function __construct(array $attrs = [])
        {
            foreach ($attrs as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a real WP_Post instance with sensible defaults.
 *
 * @param array<string, mixed> $attrs
 * @return \WP_Post
 */
function makePost(array $attrs = []): \WP_Post {
    return new \WP_Post(array_merge([
        'ID'           => 42,
        'post_title'   => 'Test Post',
        'post_name'    => 'test-post',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'post',
        'post_author'  => '1',
    ], $attrs));
}

// ---------------------------------------------------------------------------
// Shared mock setup
// ---------------------------------------------------------------------------

/**
 * Register all WP escape/translation/sanitize stubs needed for render_dropdown.
 */
function stubEscapeAndI18n(): void {
    Functions\stubs([
        'esc_attr'     => static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'),
        'esc_html'     => static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'),
        'esc_url'      => static fn($v) => (string) $v,
        '__'           => static fn($t, $d = '') => $t,
        'esc_html__'   => static fn($t, $d = '') => $t,
        'esc_attr__'   => static fn($t, $d = '') => $t,
        'esc_html_e'   => static fn($t, $d = '') => null,
        'esc_attr_e'   => static fn($t, $d = '') => null,
    ]);

    // _e echoes.
    Functions\when('_e')->alias(static function (string $t, string $d = ''): void { echo $t; });
    // esc_html_e echoes escaped.
    Functions\when('esc_html_e')->alias(static function (string $t, string $d = ''): void { echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); });
}

// ---------------------------------------------------------------------------
// init() — hook registration
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::init', function () {

    it('registers all expected WordPress hooks', function () {
        $filtersAdded = [];
        $actionsAdded = [];

        Functions\when('add_filter')->alias(static function (string $tag, mixed $cb, int $prio = 10, int $args = 1) use (&$filtersAdded): bool {
            $filtersAdded[] = $tag;
            return true;
        });

        Functions\when('add_action')->alias(static function (string $tag, mixed $cb, int $prio = 10, int $args = 1) use (&$actionsAdded): bool {
            $actionsAdded[] = $tag;
            return true;
        });

        $dropdown = new MarkdownDropdown();
        $dropdown->init();

        expect($filtersAdded)->toContain('query_vars');
        expect($filtersAdded)->toContain('the_content');
        expect($actionsAdded)->toContain('template_redirect');
        expect($actionsAdded)->toContain('wp_ajax_crispy_get_markdown');
        expect($actionsAdded)->toContain('wp_ajax_nopriv_crispy_get_markdown');
        expect($actionsAdded)->toContain('wp_ajax_crispy_track_markdown_action');
        expect($actionsAdded)->toContain('wp_ajax_nopriv_crispy_track_markdown_action');
    });

});

// ---------------------------------------------------------------------------
// add_query_vars
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::add_query_vars', function () {

    it('appends crispy_raw to the vars array', function () {
        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->add_query_vars(['page', 'p']);
        expect($result)->toContain('crispy_raw');
    });

    it('preserves existing query vars', function () {
        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->add_query_vars(['page', 'p']);
        expect($result)->toContain('page');
        expect($result)->toContain('p');
    });

    it('works with an empty array', function () {
        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->add_query_vars([]);
        expect($result)->toBe(['crispy_raw']);
    });

});

// ---------------------------------------------------------------------------
// handle_raw_request — early-return (no query var)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::handle_raw_request — no query var', function () {

    it('returns early when query var is falsy', function () {
        Functions\when('get_query_var')->justReturn('');

        $dropdown = new MarkdownDropdown();
        // Should return without calling wp_die or get_queried_object_id.
        $dropdown->handle_raw_request();

        expect(true)->toBeTrue(); // reached here — no exception.
    });

});

// ---------------------------------------------------------------------------
// handle_raw_request — post not found (wp_die path)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::handle_raw_request — post not found', function () {

    it('calls wp_die when queried object id is zero', function () {
        Functions\when('get_query_var')->justReturn('1');
        Functions\when('get_queried_object_id')->justReturn(0);
        Functions\when('esc_html__')->alias(static fn($t, $d = '') => $t);

        Functions\expect('wp_die')
            ->once()
            ->andThrow(new \RuntimeException('wp_die called'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->handle_raw_request())->toThrow(\RuntimeException::class, 'wp_die called');
    });

});

// ---------------------------------------------------------------------------
// handle_raw_request — empty markdown (wp_die path)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::handle_raw_request — empty markdown', function () {

    it('calls wp_die when post has no content', function () {
        Functions\when('get_query_var')->justReturn('1');
        Functions\when('get_queried_object_id')->justReturn(99);
        Functions\when('esc_html__')->alias(static fn($t, $d = '') => $t);

        // Native meta empty, post has no content.
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(makePost(['ID' => 99, 'post_content' => '']));

        Functions\expect('wp_die')
            ->once()
            ->andThrow(new \RuntimeException('wp_die no content'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->handle_raw_request())->toThrow(\RuntimeException::class, 'wp_die no content');
    });

});

// ---------------------------------------------------------------------------
// prepend_dropdown — not singular
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::prepend_dropdown — conditions', function () {

    it('returns content unchanged when not singular', function () {
        Functions\when('is_singular')->justReturn(false);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');
        expect($result)->toBe('<p>Content</p>');
    });

    it('returns content unchanged when post is null', function () {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_post')->justReturn(null);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');
        expect($result)->toBe('<p>Content</p>');
    });

    it('returns content unchanged when post type does not support editor', function () {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_post')->justReturn(makePost(['post_type' => 'attachment']));
        Functions\when('post_type_supports')->justReturn(false);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');
        expect($result)->toBe('<p>Content</p>');
    });

    it('returns content unchanged when is_feed', function () {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_post')->justReturn(makePost());
        Functions\when('post_type_supports')->justReturn(true);
        Functions\when('is_feed')->justReturn(true);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');
        expect($result)->toBe('<p>Content</p>');
    });

    it('prepends dropdown when conditions are met', function () {
        Functions\when('is_singular')->justReturn(true);
        $post = makePost(['ID' => 42]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('post_type_supports')->justReturn(true);
        Functions\when('is_feed')->justReturn(false);

        // Mocks for render_dropdown internals.
        Functions\when('add_query_arg')->justReturn('https://example.com/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/test-post/');
        Functions\when('get_post_meta')->justReturn('');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');

        expect($result)->toContain('crispy-markdown-dropdown');
        expect($result)->toContain('<p>Content</p>');
    });

    it('prepends dropdown at the front of content', function () {
        Functions\when('is_singular')->justReturn(true);
        $post = makePost(['ID' => 5]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('post_type_supports')->justReturn(true);
        Functions\when('is_feed')->justReturn(false);
        Functions\when('add_query_arg')->justReturn('https://example.com/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('get_post_meta')->justReturn('');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->prepend_dropdown('<p>Content</p>');

        expect(strpos($result, 'crispy-markdown-dropdown'))->toBeLessThan(strpos($result, '<p>Content</p>'));
    });

});

// ---------------------------------------------------------------------------
// render_dropdown — has_native_markdown branches
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::render_dropdown', function () {

    it('renders "View original Markdown source" when post has native markdown', function () {
        Functions\when('get_post_meta')->justReturn('# Native Markdown');
        Functions\when('add_query_arg')->justReturn('https://example.com/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $html     = $dropdown->render_dropdown(42);

        expect($html)->toContain('View original Markdown source');
    });

    it('renders "View converted Markdown" when post has no native markdown', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('add_query_arg')->justReturn('https://example.com/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $html     = $dropdown->render_dropdown(42);

        expect($html)->toContain('View converted Markdown');
    });

    it('includes data-post-id attribute', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('add_query_arg')->justReturn('https://example.com/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $html     = $dropdown->render_dropdown(99);

        expect($html)->toContain('data-post-id="99"');
    });

    it('includes the raw URL in the view link', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('add_query_arg')->justReturn('https://example.com/test/?crispy_raw=1');
        Functions\when('get_permalink')->justReturn('https://example.com/test/');

        stubEscapeAndI18n();

        $dropdown = new MarkdownDropdown();
        $html     = $dropdown->render_dropdown(7);

        expect($html)->toContain('https://example.com/test/?crispy_raw=1');
    });

});

// ---------------------------------------------------------------------------
// has_native_markdown
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::has_native_markdown', function () {

    it('returns true when meta has non-empty string', function () {
        Functions\when('get_post_meta')->justReturn('# Title');

        $dropdown = new MarkdownDropdown();
        expect($dropdown->has_native_markdown(1))->toBeTrue();
    });

    it('returns false when meta is empty', function () {
        Functions\when('get_post_meta')->justReturn('');

        $dropdown = new MarkdownDropdown();
        expect($dropdown->has_native_markdown(1))->toBeFalse();
    });

    it('returns false when meta is not a string', function () {
        Functions\when('get_post_meta')->justReturn(false);

        $dropdown = new MarkdownDropdown();
        expect($dropdown->has_native_markdown(1))->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// get_markdown_for_post — native meta path
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::get_markdown_for_post — native markdown', function () {

    it('returns native markdown from post meta when available', function () {
        Functions\when('get_post_meta')->justReturn('# My Markdown');

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->get_markdown_for_post(42);
        expect($result)->toBe('# My Markdown');
    });

});

// ---------------------------------------------------------------------------
// get_markdown_for_post — HTML fallback path
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::get_markdown_for_post — HTML fallback', function () {

    it('returns empty string when post has no content', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(makePost(['post_content' => '']));

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->get_markdown_for_post(42);
        expect($result)->toBe('');
    });

    it('returns empty string when post is null', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(null);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->get_markdown_for_post(42);
        expect($result)->toBe('');
    });

    it('converts HTML post content via HtmlToMarkdownConverter', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(makePost(['post_content' => '<p>Hello world</p>']));
        // HtmlToMarkdownConverter calls apply_filters.
        Functions\when('apply_filters')->alias(static fn($tag, $value, ...$args) => $value);

        $dropdown = new MarkdownDropdown();
        $result   = $dropdown->get_markdown_for_post(42);
        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — nonce failure
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — nonce failure', function () {

    it('sends 403 error when nonce is invalid', function () {
        Functions\when('check_ajax_referer')->justReturn(false);
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 403)
            ->andThrow(new \RuntimeException('json_error_403'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_error_403');
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — invalid post ID
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — invalid post ID', function () {

    it('sends 400 error when post ID is zero', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        // Ensure $_POST['post_id'] is absent / zero.
        unset($_POST['post_id']);

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 400)
            ->andThrow(new \RuntimeException('json_error_400'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_error_400');
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — post not found
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — post not found', function () {

    it('sends 404 error when post does not exist', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '42';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('get_post')->justReturn(null);

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 404)
            ->andThrow(new \RuntimeException('json_error_404'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_error_404');

        unset($_POST['post_id']);
    });

    it('sends 404 error when post is not published', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '42';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('get_post')->justReturn(makePost(['post_status' => 'draft']));

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 404)
            ->andThrow(new \RuntimeException('json_error_404_draft'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_error_404_draft');

        unset($_POST['post_id']);
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — empty markdown
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — empty markdown', function () {

    it('sends 404 error when no content is available', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '42';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('get_post')->justReturn(makePost(['post_content' => '']));
        Functions\when('get_post_meta')->justReturn('');

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 404)
            ->andThrow(new \RuntimeException('json_error_404_empty'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_error_404_empty');

        unset($_POST['post_id']);
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — success path (covers build_markdown_output)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — success', function () {

    it('sends success with markdown output for valid post with native markdown', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '42';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 42, 'post_title' => 'Hello Post', 'post_name' => 'hello-post', 'post_author' => 1]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Hello World');

        // build_markdown_output calls.
        Functions\when('get_the_author_meta')->justReturn('Test Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/hello-post/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);

        // get_shortcode_replacements calls get_option.
        Functions\when('get_option')->justReturn('');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(static fn($data) => isset($data['markdown']) && str_contains($data['markdown'], 'Hello Post')))
            ->andThrow(new \RuntimeException('json_success'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_get_markdown())->toThrow(\RuntimeException::class, 'json_success');

        unset($_POST['post_id']);
    });

    it('includes YAML frontmatter in the output', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '7';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 7, 'post_title' => 'YAML Test', 'post_name' => 'yaml-test', 'post_author' => 2]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Content');
        Functions\when('get_the_author_meta')->justReturn('Jane Doe');
        Functions\when('get_the_date')->justReturn('2026-06-01');
        Functions\when('get_permalink')->justReturn('https://example.com/yaml-test/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);
        Functions\when('get_option')->justReturn('');

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('json_success_captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($captured)->not->toBeNull();
        expect($captured['markdown'])->toContain('---');
        expect($captured['markdown'])->toContain('title: "YAML Test"');
        expect($captured['markdown'])->toContain('author: "Jane Doe"');
        expect($captured['markdown'])->toContain('date: 2026-06-01');

        unset($_POST['post_id']);
    });

    it('includes categories and tags in YAML frontmatter', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '8';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 8, 'post_title' => 'Tagged Post', 'post_name' => 'tagged-post', 'post_author' => 1]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Content');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/tagged-post/');
        Functions\when('wp_get_post_categories')->justReturn(['AI', 'Marketing']);
        Functions\when('wp_get_post_tags')->justReturn(['llm', 'gpt']);
        Functions\when('get_option')->justReturn('');

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($captured['markdown'])->toContain('categories:');
        expect($captured['markdown'])->toContain('  - "AI"');
        expect($captured['markdown'])->toContain('tags:');
        expect($captured['markdown'])->toContain('  - "llm"');

        unset($_POST['post_id']);
    });

    it('handles post title with double quotes in YAML output', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '9';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 9, 'post_title' => 'He said "Hello"', 'post_name' => 'quoted-title', 'post_author' => 1]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Content');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);
        Functions\when('get_option')->justReturn('');

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        // Quotes should be escaped in YAML.
        expect($captured['markdown'])->toContain('\\"Hello\\"');

        unset($_POST['post_id']);
    });

});

// ---------------------------------------------------------------------------
// ajax_get_markdown — success with HTML fallback (covers get_converter path)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_get_markdown — HTML fallback conversion', function () {

    it('converts HTML content when no native markdown meta exists', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '10';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 10, 'post_title' => 'HTML Post', 'post_name' => 'html-post', 'post_author' => 1, 'post_content' => '<p>Converted content</p>']);
        Functions\when('get_post')->justReturn($post);
        // Native meta empty — first call from get_markdown_for_post; second call from get_markdown_for_post fallback
        Functions\when('get_post_meta')->justReturn('');
        // HtmlToMarkdownConverter::convert calls apply_filters.
        Functions\when('apply_filters')->alias(static fn($tag, $value, ...$args) => $value);

        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/html-post/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);
        Functions\when('get_option')->justReturn('');

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($captured)->not->toBeNull();
        expect($captured['markdown'])->toContain('HTML Post');

        unset($_POST['post_id']);
    });

});

// ---------------------------------------------------------------------------
// expand_shortcodes (via ajax_get_markdown success path)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown shortcode expansion', function () {

    it('expands registered shortcodes in markdown content', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '11';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 11, 'post_title' => 'Shortcode Post', 'post_name' => 'shortcode-post', 'post_author' => 1]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('[newsletter_cta]');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);

        // Return a multi-line shortcode option.
        Functions\when('get_option')->alias(static function ($key, $default = '') {
            if ($key === 'crispytheme_markdown_shortcodes') {
                return "[newsletter_cta]=Sign up for my newsletter\n[empty_line]\n[no_equals_here]\n=ignored_empty_key\n";
            }
            return $default;
        });

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($captured['markdown'])->toContain('Sign up for my newsletter');

        unset($_POST['post_id']);
    });

    it('handles shortcodes option with no valid entries', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id'] = '12';
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));

        $post = makePost(['ID' => 12, 'post_title' => 'Post', 'post_name' => 'post', 'post_author' => 1]);
        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Just content');
        Functions\when('get_the_author_meta')->justReturn('Author');
        Functions\when('get_the_date')->justReturn('2026-01-01');
        Functions\when('get_permalink')->justReturn('https://example.com/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);
        Functions\when('get_option')->justReturn('');

        $captured = null;
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(static function ($data) use (&$captured): void {
                $captured = $data;
                throw new \RuntimeException('captured');
            });

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_get_markdown();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($captured['markdown'])->toContain('Just content');

        unset($_POST['post_id']);
    });

});

// ---------------------------------------------------------------------------
// ajax_track_action — nonce failure
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_track_action — nonce failure', function () {

    it('sends 403 error when tracking nonce is invalid', function () {
        Functions\when('check_ajax_referer')->justReturn(false);
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 403)
            ->andThrow(new \RuntimeException('track_403'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_403');
    });

});

// ---------------------------------------------------------------------------
// ajax_track_action — invalid post ID
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_track_action — invalid post ID', function () {

    it('sends 400 error when post ID is zero', function () {
        Functions\when('check_ajax_referer')->justReturn(true);
        unset($_POST['post_id'], $_POST['action_type']);

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 400)
            ->andThrow(new \RuntimeException('track_400_post_id'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_400_post_id');
    });

});

// ---------------------------------------------------------------------------
// ajax_track_action — invalid action type
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_track_action — invalid action type', function () {

    it('sends 400 error when action_type is not copy or view', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '42';
        $_POST['action_type'] = 'delete';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $v)));

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 400)
            ->andThrow(new \RuntimeException('track_400_action'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_400_action');

        unset($_POST['post_id'], $_POST['action_type']);
    });

});

// ---------------------------------------------------------------------------
// ajax_track_action — post not found / not published
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_track_action — post not found', function () {

    it('sends 404 error when post does not exist', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '42';
        $_POST['action_type'] = 'copy';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(null);

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 404)
            ->andThrow(new \RuntimeException('track_404'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_404');

        unset($_POST['post_id'], $_POST['action_type']);
    });

    it('sends 404 error when post is not published', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '42';
        $_POST['action_type'] = 'view';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(makePost(['post_status' => 'pending']));

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(Mockery::type('array'), 404)
            ->andThrow(new \RuntimeException('track_404_pending'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_404_pending');

        unset($_POST['post_id'], $_POST['action_type']);
    });

});

// ---------------------------------------------------------------------------
// ajax_track_action — success paths (copy and view)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::ajax_track_action — success', function () {

    it('tracks copy action and increments per-post and aggregate counts', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '42';
        $_POST['action_type'] = 'copy';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(makePost());

        // Per-post meta.
        Functions\when('get_post_meta')->justReturn('5');
        Functions\when('update_post_meta')->justReturn(true);

        // Aggregate option.
        Functions\when('get_option')->justReturn(['total_copies' => 10, 'total_views' => 3]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('current_time')->justReturn('2026-06-19 12:00:00');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(static fn($data) => isset($data['tracked']) && $data['tracked'] === true))
            ->andThrow(new \RuntimeException('track_success'));

        $dropdown = new MarkdownDropdown();
        expect(fn() => $dropdown->ajax_track_action())->toThrow(\RuntimeException::class, 'track_success');

        unset($_POST['post_id'], $_POST['action_type']);
    });

    it('tracks view action and uses view meta key', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '99';
        $_POST['action_type'] = 'view';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(makePost(['ID' => 99]));

        $updatedKey   = null;
        $updatedValue = null;

        Functions\when('get_post_meta')->justReturn('0');
        Functions\when('update_post_meta')->alias(static function ($pid, $key, $val) use (&$updatedKey, &$updatedValue): bool {
            $updatedKey   = $key;
            $updatedValue = $val;
            return true;
        });

        Functions\when('get_option')->justReturn(['total_copies' => 0, 'total_views' => 0]);
        Functions\when('update_option')->justReturn(true);
        Functions\when('current_time')->justReturn('2026-06-19 12:00:00');

        Functions\expect('wp_send_json_success')
            ->once()
            ->andThrow(new \RuntimeException('track_view_success'));

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_track_action();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($updatedKey)->toBe(MarkdownDropdown::META_VIEW_COUNT);
        expect($updatedValue)->toBe(1);

        unset($_POST['post_id'], $_POST['action_type']);
    });

    it('increments aggregate total_views counter for view action', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '55';
        $_POST['action_type'] = 'view';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(makePost(['ID' => 55]));
        Functions\when('get_post_meta')->justReturn('2');
        Functions\when('update_post_meta')->justReturn(true);

        $savedStats = null;
        Functions\when('get_option')->justReturn(['total_copies' => 5, 'total_views' => 7]);
        Functions\when('update_option')->alias(static function ($key, $value) use (&$savedStats): bool {
            $savedStats = $value;
            return true;
        });
        Functions\when('current_time')->justReturn('2026-06-19 09:00:00');

        Functions\expect('wp_send_json_success')
            ->once()
            ->andThrow(new \RuntimeException('done'));

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_track_action();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($savedStats['total_views'])->toBe(8);
        expect($savedStats['total_copies'])->toBe(5);

        unset($_POST['post_id'], $_POST['action_type']);
    });

    it('increments aggregate total_copies counter for copy action', function () {
        Functions\when('check_ajax_referer')->justReturn(true);

        $_POST['post_id']    = '66';
        $_POST['action_type'] = 'copy';

        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
        Functions\when('sanitize_key')->alias(static fn($v) => (string) $v);
        Functions\when('get_post')->justReturn(makePost(['ID' => 66]));
        Functions\when('get_post_meta')->justReturn('0');
        Functions\when('update_post_meta')->justReturn(true);

        $savedStats = null;
        Functions\when('get_option')->justReturn(['total_copies' => 2, 'total_views' => 9]);
        Functions\when('update_option')->alias(static function ($key, $value) use (&$savedStats): bool {
            $savedStats = $value;
            return true;
        });
        Functions\when('current_time')->justReturn('2026-06-19 10:00:00');

        Functions\expect('wp_send_json_success')
            ->once()
            ->andThrow(new \RuntimeException('done'));

        $dropdown = new MarkdownDropdown();
        try {
            $dropdown->ajax_track_action();
        } catch (\RuntimeException $e) {
            // expected.
        }

        expect($savedStats['total_copies'])->toBe(3);
        expect($savedStats['last_updated'])->toBe('2026-06-19 10:00:00');

        unset($_POST['post_id'], $_POST['action_type']);
    });

});

// ---------------------------------------------------------------------------
// get_top_posts — via $wpdb mock
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::get_top_posts', function () {

    beforeEach(function () {
        if (! defined('ARRAY_A')) {
            define('ARRAY_A', 'ARRAY_A');
        }
    });

    it('returns empty array when wpdb returns no results', function () {
        $wpdb             = new \stdClass();
        $wpdb->posts      = 'wp_posts';
        $wpdb->postmeta   = 'wp_postmeta';
        $wpdb->prepare    = static fn($sql, ...$args) => $sql;
        $wpdb->get_results = static fn($sql, $output = '') => null;

        // Use closures to make prepare/get_results callable.
        $mock            = new class {
            public string $posts    = 'wp_posts';
            public string $postmeta = 'wp_postmeta';

            public function prepare(string $sql, mixed ...$args): string {
                return $sql;
            }

            public function get_results(string $sql, string $output = ''): ?array {
                return null;
            }
        };

        $GLOBALS['wpdb'] = $mock;

        $result = MarkdownDropdown::get_top_posts(5);
        expect($result)->toBe([]);

        unset($GLOBALS['wpdb']);
    });

    it('returns cast rows when wpdb returns results', function () {
        $mock = new class {
            public string $posts    = 'wp_posts';
            public string $postmeta = 'wp_postmeta';

            public function prepare(string $sql, mixed ...$args): string {
                return $sql;
            }

            public function get_results(string $sql, string $output = ''): ?array {
                return [
                    [
                        'ID'          => '42',
                        'post_title'  => 'My Post',
                        'post_type'   => 'post',
                        'copy_count'  => '15',
                        'view_count'  => '30',
                        'total_count' => '45',
                    ],
                ];
            }
        };

        $GLOBALS['wpdb'] = $mock;

        $result = MarkdownDropdown::get_top_posts(10);

        expect($result)->toHaveCount(1);
        expect($result[0]['ID'])->toBe(42);
        expect($result[0]['copy_count'])->toBe(15);
        expect($result[0]['view_count'])->toBe(30);
        expect($result[0]['total_count'])->toBe(45);
        expect($result[0]['post_title'])->toBe('My Post');

        unset($GLOBALS['wpdb']);
    });

    it('casts all numeric fields to integers', function () {
        $mock = new class {
            public string $posts    = 'wp_posts';
            public string $postmeta = 'wp_postmeta';

            public function prepare(string $sql, mixed ...$args): string {
                return $sql;
            }

            public function get_results(string $sql, string $output = ''): ?array {
                return [
                    [
                        'ID'          => '1',
                        'post_title'  => 'P',
                        'post_type'   => 'post',
                        'copy_count'  => '0',
                        'view_count'  => '0',
                        'total_count' => '0',
                    ],
                ];
            }
        };

        $GLOBALS['wpdb'] = $mock;

        $result = MarkdownDropdown::get_top_posts();
        expect($result[0]['ID'])->toBeInt();
        expect($result[0]['copy_count'])->toBeInt();

        unset($GLOBALS['wpdb']);
    });

});

// ---------------------------------------------------------------------------
// Static methods (already partially covered by MarkdownDropdownAnalyticsTest)
// ---------------------------------------------------------------------------

describe('MarkdownDropdown static methods', function () {

    it('get_nonce_action returns expected string', function () {
        expect(MarkdownDropdown::get_nonce_action())->toBe('crispy_markdown_dropdown');
    });

    it('get_tracking_nonce_action returns expected string', function () {
        expect(MarkdownDropdown::get_tracking_nonce_action())->toBe('crispy_markdown_analytics');
    });

    it('QUERY_VAR constant is crispy_raw', function () {
        expect(MarkdownDropdown::QUERY_VAR)->toBe('crispy_raw');
    });

});

// ---------------------------------------------------------------------------
// handle_raw_request — success orchestration (lines 124-127)
// Covers: get_post(), build_markdown_output(), output_raw_markdown() call.
// output_raw_markdown() itself is @codeCoverageIgnore (contains exit).
// ---------------------------------------------------------------------------

describe('MarkdownDropdown::handle_raw_request — success orchestration', function () {

    it('builds output and passes it to output_raw_markdown on a valid request', function () {
        // Subclass overrides output_raw_markdown to intercept without exiting.
        $capturedOutput   = null;
        $capturedFilename = null;

        $dropdown = new class (
            $capturedOutput,
            $capturedFilename
        ) extends MarkdownDropdown {
            /** @var string|null */
            private mixed $captureOutput;
            /** @var string|null */
            private mixed $captureFilename;

            public function __construct(mixed &$captureOutput, mixed &$captureFilename)
            {
                $this->captureOutput   = &$captureOutput;
                $this->captureFilename = &$captureFilename;
            }

            protected function output_raw_markdown(string $output, string $filename): void
            {
                $this->captureOutput   = $output;
                $this->captureFilename = $filename;
                // Intentionally do NOT call exit — return normally so test continues.
            }
        };

        // Guards pass: query var is set, queried object returns a valid id.
        Functions\when('get_query_var')->justReturn('1');
        Functions\when('get_queried_object_id')->justReturn(42);
        Functions\when('esc_html__')->alias(static fn($t, $d = '') => $t);

        // get_markdown_for_post uses get_post_meta to check for native markdown.
        Functions\when('get_post_meta')->justReturn('# Hello Markdown');

        // build_markdown_output needs a WP_Post from get_post().
        $post = makePost([
            'ID'          => 42,
            'post_title'  => 'Success Test',
            'post_name'   => 'success-test',
            'post_author' => '1',
        ]);
        Functions\when('get_post')->justReturn($post);

        // build_markdown_output internals.
        Functions\when('get_the_author_meta')->justReturn('Test Author');
        Functions\when('get_the_date')->justReturn('2026-06-19');
        Functions\when('get_permalink')->justReturn('https://example.com/success-test/');
        Functions\when('wp_get_post_categories')->justReturn([]);
        Functions\when('wp_get_post_tags')->justReturn([]);

        // expand_shortcodes reads option.
        Functions\when('get_option')->justReturn('');

        $dropdown->handle_raw_request();

        expect($capturedOutput)->not->toBeNull();
        expect($capturedOutput)->toContain('Success Test');
        expect($capturedOutput)->toContain('---');
        expect($capturedFilename)->toBe('success-test');
    });

});
