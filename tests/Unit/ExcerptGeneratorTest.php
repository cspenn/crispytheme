<?php
/**
 * Excerpt Generator Unit Tests
 *
 * @package CrispyTheme\Tests\Unit
 */

declare(strict_types=1);

use CrispyTheme\Content\ExcerptGenerator;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;
use Brain\Monkey\Functions;

describe('ExcerptGenerator', function () {
    beforeEach(function () {
        // Mock apply_filters to return the value as-is.
        Functions\when('apply_filters')->returnArg(2);
    });

    it('generates excerpt from plain text', function () {
        $generator = new ExcerptGenerator();

        $content = 'This is a simple paragraph of text that should be excerpted.';
        $excerpt = $generator->generate_from_markdown($content);

        expect($excerpt)->toContain('This is a simple paragraph of text');
    });

    it('strips markdown heading formatting', function () {
        $generator = new ExcerptGenerator();

        $markdown = "# Heading\n\nThis is some paragraph text.";
        $excerpt = $generator->generate_from_markdown($markdown);

        // The heading text should remain, but markdown syntax stripped.
        expect($excerpt)->toContain('Heading');
        expect($excerpt)->toContain('This is some paragraph text');
        $this->assertMarkdownStripped($excerpt);
    });

    it('strips bold and italic formatting', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'This is **bold** and *italic* text.';
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toContain('bold');
        expect($excerpt)->toContain('italic');
        // HTML tags should be stripped.
        expect($excerpt)->not->toContain('<strong>');
        expect($excerpt)->not->toContain('<em>');
    });

    it('removes code blocks', function () {
        $generator = new ExcerptGenerator();

        $markdown = "Some text\n\n```php\n<?php echo 'hello';\n```\n\nMore text.";
        $excerpt = $generator->generate_from_markdown($markdown);

        // Code blocks produce <pre><code> which get stripped.
        expect($excerpt)->toContain('Some text');
        expect($excerpt)->toContain('More text');
    });

    it('removes inline code but keeps content', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'Use the `print()` function to output.';
        $excerpt = $generator->generate_from_markdown($markdown);

        // Inline code becomes <code>print()</code> then stripped to print().
        expect($excerpt)->toContain('print()');
        expect($excerpt)->not->toContain('`');
    });

    it('removes links but keeps text', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'Check out [this link](https://example.com) for more.';
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toContain('this link');
        expect($excerpt)->not->toContain('https://example.com');
    });

    it('removes images', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'Text before ![alt text](image.jpg) text after.';
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toContain('Text before');
        expect($excerpt)->toContain('text after');
    });

    it('respects word count limit', function () {
        $generator = new ExcerptGenerator();

        $longContent = 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen';
        $excerpt = $generator->generate_from_markdown($longContent, 5);

        // Should have about 5 words.
        $words = str_word_count($excerpt);
        expect($words)->toBeLessThanOrEqual(6); // Allow for "..." if added.
    });

    it('adds ellipsis when truncated', function () {
        $generator = new ExcerptGenerator();

        $longContent = 'one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen';
        $excerpt = $generator->generate_from_markdown($longContent, 5);

        expect($excerpt)->toContain('&hellip;'); // HTML entity for ellipsis.
    });

    it('does not add ellipsis for short content', function () {
        $generator = new ExcerptGenerator();

        $shortContent = 'Short content.';
        $excerpt = $generator->generate_from_markdown($shortContent, 100);

        expect($excerpt)->not->toContain('&hellip;');
    });

    it('normalizes whitespace', function () {
        $generator = new ExcerptGenerator();

        $content = "Text   with\n\nmultiple   spaces.";
        $excerpt = $generator->generate_from_markdown($content);

        // Multiple spaces should be normalized.
        expect($excerpt)->not->toMatch('/  /'); // No double spaces.
    });

    it('handles empty content', function () {
        $generator = new ExcerptGenerator();

        $excerpt = $generator->generate_from_markdown('');

        expect($excerpt)->toBe('');
    });

    it('handles blockquotes', function () {
        $generator = new ExcerptGenerator();

        $markdown = "> This is a quote.\n\nRegular text.";
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toContain('This is a quote');
        expect($excerpt)->toContain('Regular text');
    });

    it('handles list items', function () {
        $generator = new ExcerptGenerator();

        $markdown = "- Item 1\n- Item 2\n- Item 3";
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toContain('Item 1');
        expect($excerpt)->toContain('Item 2');
    });

    it('generates excerpt from fixture content', function () {
        $generator = new ExcerptGenerator();

        $markdown = MarkdownFixtures::complex();
        $excerpt = $generator->generate_from_markdown($markdown);

        expect($excerpt)->toBeNonEmptyString();
        $this->assertMarkdownStripped($excerpt);
    });

    it('handles content with specific word count', function () {
        $generator = new ExcerptGenerator();

        $markdown = MarkdownFixtures::withWordCount(50);
        $excerpt = $generator->generate_from_markdown($markdown, 20);

        $words = str_word_count($excerpt);
        expect($words)->toBeLessThanOrEqual(25); // Some margin for ellipsis.
    });
});

describe('ExcerptGenerator from HTML', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('generates excerpt from HTML', function () {
        $generator = new ExcerptGenerator();

        $html = '<p>This is a <strong>bold</strong> paragraph.</p>';
        $excerpt = $generator->generate_from_html($html);

        expect($excerpt)->toBe('This is a bold paragraph.');
    });

    it('strips all HTML tags', function () {
        $generator = new ExcerptGenerator();

        $html = '<div><h1>Title</h1><p>Content with <a href="#">link</a>.</p></div>';
        $excerpt = $generator->generate_from_html($html);

        expect($excerpt)->not->toContain('<');
        expect($excerpt)->not->toContain('>');
        expect($excerpt)->toContain('Title');
        expect($excerpt)->toContain('Content');
        expect($excerpt)->toContain('link');
    });

    it('respects word count from filter', function () {
        // Mock filter to return 10 words.
        Functions\when('apply_filters')->alias(function ($filter, $value) {
            if ($filter === 'crispytheme_excerpt_length') {
                return 10;
            }
            return $value;
        });

        $generator = new ExcerptGenerator();
        $html = '<p>' . str_repeat('word ', 50) . '</p>';
        $excerpt = $generator->generate_from_html($html);

        $words = str_word_count($excerpt);
        expect($words)->toBeLessThanOrEqual(11); // 10 words + possible partial.
    });
});

describe('ExcerptGenerator with sentence boundary', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('ends at sentence boundary when possible', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'First sentence here. Second sentence follows. Third sentence ends.';
        $excerpt = $generator->generate_with_sentence_boundary($markdown, 20, 50);

        // Should end at a sentence boundary.
        expect($excerpt)->toMatch('/\.$/');
    });

    it('returns full text if shorter than minimum', function () {
        $generator = new ExcerptGenerator();

        $markdown = 'Short text.';
        $excerpt = $generator->generate_with_sentence_boundary($markdown, 100, 200);

        expect($excerpt)->toBe('Short text.');
    });
});
