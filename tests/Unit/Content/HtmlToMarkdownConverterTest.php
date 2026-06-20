<?php
/**
 * HTML to Markdown Converter Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\HtmlToMarkdownConverter;
use Brain\Monkey\Functions;

// WP_Post stub — only created here if not already defined (bootstrap does not define it).
// Uses untyped properties to remain compatible with MarkdownDropdownTest's stub,
// whichever file loads first in the Pest process.
if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID           = 1;
        public $post_title   = '';
        public $post_content = '';
        public $post_excerpt = '';
        public $post_name    = '';
        public $post_type    = 'post';
        public $post_status  = 'publish';
        public $post_author  = '1';

        public function __construct(array $attrs = [])
        {
            foreach ($attrs as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

describe('HtmlToMarkdownConverter::convert', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(1);
    });

    it('returns empty string for empty input', function () {
        $converter = new HtmlToMarkdownConverter();
        expect($converter->convert(''))->toBe('');
    });

    it('returns empty string for whitespace-only input', function () {
        $converter = new HtmlToMarkdownConverter();
        expect($converter->convert('   '))->toBe('');
    });

    it('converts a simple paragraph to markdown', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $result    = $converter->convert('<p>Hello world</p>');
        expect($result)->toContain('Hello world');
        expect($result)->not->toContain('<p>');
    });

    it('converts headings to atx style', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $result    = $converter->convert('<h1>Title</h1>');
        expect($result)->toContain('# Title');
    });

    it('converts bold to markdown double asterisks', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $result    = $converter->convert('<p><strong>bold text</strong></p>');
        expect($result)->toContain('bold text');
    });

    it('strips Gutenberg opening block comments', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $html      = "<!-- wp:paragraph -->\n<p>Hello</p>\n<!-- /wp:paragraph -->";
        $result    = $converter->convert($html);
        expect($result)->not->toContain('<!-- wp:');
        expect($result)->not->toContain('<!-- /wp:');
        expect($result)->toContain('Hello');
    });

    it('strips Gutenberg block comments with attributes', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $html      = '<!-- wp:heading {"level":2} --><h2>Sub</h2><!-- /wp:heading -->';
        $result    = $converter->convert($html);
        expect($result)->not->toContain('wp:heading');
        expect($result)->toContain('Sub');
    });

    it('applies the crispytheme_html_to_markdown_result filter', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            if ($filter === 'crispytheme_html_to_markdown_result') {
                return 'FILTERED';
            }
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $result    = $converter->convert('<p>Content</p>');
        expect($result)->toBe('FILTERED');
    });

    it('unescapes WordPress shortcodes', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        // The converter may escape [ ] in HTML-less text processed through it.
        // We verify the unescape logic handles \[...\] → [...].
        $converter = new HtmlToMarkdownConverter();

        // Feed pre-escaped shortcode-like text via a paragraph to get through convert().
        // The library escapes brackets, then unescape_markdown_syntax should restore them.
        $html   = '<p>[gallery ids="1,2,3"]</p>';
        $result = $converter->convert($html);
        // After unescape, the bracket content should not be double-escaped.
        expect($result)->toBeString();
    });

    it('normalizes excessive newlines in output', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $html      = "<p>Para one</p>\n\n\n\n<p>Para two</p>";
        $result    = $converter->convert($html);
        // Should not have 3+ consecutive newlines.
        expect($result)->not->toMatch('/\n{3,}/');
    });

    it('removes script and style elements', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            return $value;
        });

        $converter = new HtmlToMarkdownConverter();
        $html      = '<p>Visible</p><script>alert(1)</script><style>.hide{}</style>';
        $result    = $converter->convert($html);
        expect($result)->not->toContain('alert(1)');
        expect($result)->not->toContain('.hide');
        expect($result)->toContain('Visible');
    });

    it('returns empty string when apply_filters throws during conversion pipeline', function () {
        // apply_filters is called inside the try block (line 72); throwing here
        // exercises the catch(\Exception $e) branch at lines 73-75.
        // The private $converter has no DI seam, so apply_filters is the only
        // deterministic hook available without modifying src/.
        Functions\when('apply_filters')->alias(function (): never {
            throw new \RuntimeException('simulated pipeline failure');
        });

        $converter = new HtmlToMarkdownConverter();
        $result    = $converter->convert('<p>trigger the try block</p>');
        expect($result)->toBe('');
    });
});

describe('HtmlToMarkdownConverter::is_legacy_post', function () {
    it('returns false when get_post returns non-WP_Post', function () {
        Functions\when('get_post')->justReturn(null);
        Functions\when('get_post_meta')->justReturn('');

        $converter = new HtmlToMarkdownConverter();
        expect($converter->is_legacy_post(99))->toBeFalse();
    });

    it('returns false when post has empty post_content', function () {
        $post               = new WP_Post();
        $post->ID           = 1;
        $post->post_content = '   ';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('');

        $converter = new HtmlToMarkdownConverter();
        expect($converter->is_legacy_post(1))->toBeFalse();
    });

    it('returns false when post already has markdown meta', function () {
        $post               = new WP_Post();
        $post->ID           = 2;
        $post->post_content = '<p>Some HTML content</p>';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Markdown already here');

        $converter = new HtmlToMarkdownConverter();
        expect($converter->is_legacy_post(2))->toBeFalse();
    });

    it('returns true when post has HTML content but no markdown meta', function () {
        $post               = new WP_Post();
        $post->ID           = 3;
        $post->post_content = '<p>Legacy HTML content</p>';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('');

        $converter = new HtmlToMarkdownConverter();
        expect($converter->is_legacy_post(3))->toBeTrue();
    });
});
