<?php
/**
 * Markdown Renderer Integration Tests
 *
 * @package CrispyTheme\Tests\Integration
 */

declare(strict_types=1);

use CrispyTheme\Content\MarkdownRenderer;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;
use Brain\Monkey\Functions;

describe('MarkdownRenderer', function () {
    beforeEach(function () {
        // Use the MocksTransients trait from TestCase.
        $this->setupTransients();

        // Mock apply_filters to return the value as-is.
        Functions\when('apply_filters')->returnArg(2);

        $this->renderer = new MarkdownRenderer();
    });

    it('renders simple markdown to HTML', function () {
        $markdown = '# Hello World';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<h1>Hello World</h1>');
        $this->assertMarkdownContainsHeading($html, 1, 'Hello World');
    });

    it('wraps content in markdown-body class', function () {
        $markdown = 'Simple paragraph.';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('class="markdown-body"');
        $this->assertMarkdownHasCssClass($html, 'markdown-body');
    });

    it('caches rendered content', function () {
        $markdown = 'Cached content.';

        // First render.
        $html1 = $this->renderer->render(1, $markdown);

        // Second render should use cache.
        $html2 = $this->renderer->render(1, $markdown);

        expect($html1)->toBe($html2);
    });

    it('produces different output for different content', function () {
        $markdown1 = 'Original content.';
        $markdown2 = 'Updated content.';

        $html1 = $this->renderer->render(1, $markdown1);
        $html2 = $this->renderer->render(1, $markdown2);

        expect($html1)->not->toBe($html2);
        expect($html1)->toContain('Original content');
        expect($html2)->toContain('Updated content');
    });

    it('renders code blocks with language class', function () {
        $markdown = "```javascript\nconsole.log('test');\n```";
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('language-javascript');
        $this->assertMarkdownContainsCodeBlock($html, 'javascript');
    });

    it('renders tables correctly', function () {
        $markdown = MarkdownFixtures::withTables();
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<table>');
        $this->assertMarkdownContainsTable($html);
    });

    it('allows raw HTML in markdown', function () {
        $markdown = '<div class="custom">Custom HTML</div>';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<div class="custom">Custom HTML</div>');
    });

    it('handles empty content', function () {
        $html = $this->renderer->render(1, '');

        expect($html)->toContain('class="markdown-body"');
    });

    it('renders nested lists correctly', function () {
        $markdown = "- Item 1\n  - Nested 1\n  - Nested 2\n- Item 2";
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<ul>');
        expect(substr_count($html, '<ul>'))->toBeGreaterThanOrEqual(2);
        $this->assertMarkdownContainsList($html, 'ul');
    });

    it('renders bold and italic text', function () {
        $markdown = 'This is **bold** and *italic* text.';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<strong>bold</strong>');
        expect($html)->toContain('<em>italic</em>');
        $this->assertMarkdownContainsBoldText($html, 'bold');
        $this->assertMarkdownContainsItalicText($html, 'italic');
    });

    it('renders links correctly', function () {
        $markdown = '[Link text](https://example.com)';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('href="https://example.com"');
        expect($html)->toContain('>Link text</a>');
        $this->assertMarkdownContainsLink($html, 'https://example.com', 'Link text');
    });

    it('renders images correctly', function () {
        $markdown = '![Alt text](https://example.com/image.jpg)';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<img');
        expect($html)->toContain('src="https://example.com/image.jpg"');
        $this->assertMarkdownContainsImage($html, 'https://example.com/image.jpg', 'Alt text');
    });

    it('renders blockquotes correctly', function () {
        $markdown = '> This is a quote.';
        $html = $this->renderer->render(1, $markdown);

        expect($html)->toContain('<blockquote>');
        $this->assertMarkdownContainsBlockquote($html);
    });

    it('renders complex markdown from fixtures', function () {
        $markdown = MarkdownFixtures::complex();
        $html = $this->renderer->render(1, $markdown);

        $this->assertMarkdownContainsHeadingLevel($html, 1);
        $this->assertMarkdownContainsHeadingLevel($html, 2);
        $this->assertMarkdownContainsBoldText($html);
        $this->assertMarkdownContainsItalicText($html);
        $this->assertMarkdownContainsCodeBlock($html, 'php');
        $this->assertMarkdownContainsList($html, 'ul');
        $this->assertMarkdownContainsBlockquote($html);
        $this->assertMarkdownContainsLink($html, 'https://example.com');
    });
});

describe('MarkdownRenderer parseWithoutCache', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);

        $this->renderer = new MarkdownRenderer();
    });

    it('renders without using cache', function () {
        $markdown = '# Preview Content';

        // parse_without_cache should not store in cache.
        $html = $this->renderer->parse_without_cache($markdown);

        expect($html)->toContain('<h1>Preview Content</h1>');
        expect($html)->toContain('class="markdown-body"');

        // Cache should be empty.
        expect($this->transientStore->getKeys())->toBeEmpty();
    });
});

describe('MarkdownRenderer container customization', function () {
    beforeEach(function () {
        $this->setupTransients();
    });

    it('allows customizing container class via filter', function () {
        // Mock apply_filters to return custom class for container.
        Functions\when('apply_filters')->alias(function ($filter, $value) {
            if ($filter === 'crispytheme_markdown_container_class') {
                return 'custom-markdown-class';
            }
            return $value;
        });

        $renderer = new MarkdownRenderer();
        $html = $renderer->render(1, '# Test');

        expect($html)->toContain('class="custom-markdown-class"');
        $this->assertMarkdownHasCssClass($html, 'custom-markdown-class');
    });
});
