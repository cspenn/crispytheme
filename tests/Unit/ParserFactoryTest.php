<?php
/**
 * Parser Factory Unit Tests
 *
 * @package CrispyTheme\Tests\Unit
 */

declare(strict_types=1);

use CrispyTheme\Parser\ParserFactory;
use CrispyTheme\Parser\ParserInterface;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;
use Brain\Monkey\Functions;

describe('ParserFactory', function () {
    beforeEach(function () {
        // Mock apply_filters to return the value as-is.
        Functions\when('apply_filters')->returnArg(2);
    });

    it('creates a parser instance', function () {
        $parser = ParserFactory::create();

        expect($parser)->toBeInstanceOf(ParserInterface::class);
    });

    it('parses basic markdown to HTML', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('# Hello World');

        expect($html)->toContain('<h1>Hello World</h1>');
        $this->assertMarkdownContainsHeading($html, 1, 'Hello World');
    });

    it('parses bold text correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('This is **bold** text.');

        expect($html)->toContain('<strong>bold</strong>');
        $this->assertMarkdownContainsBoldText($html, 'bold');
    });

    it('parses italic text correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('This is *italic* text.');

        expect($html)->toContain('<em>italic</em>');
        $this->assertMarkdownContainsItalicText($html, 'italic');
    });

    it('parses code blocks with language', function () {
        $parser = ParserFactory::create();
        $markdown = "```php\n<?php echo 'Hello';\n```";
        $html = $parser->parse($markdown);

        expect($html)->toContain('<code');
        expect($html)->toContain('language-php');
        $this->assertMarkdownContainsCodeBlock($html, 'php');
    });

    it('parses inline code correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('Use the `print()` function.');

        expect($html)->toContain('<code>print()</code>');
    });

    it('parses links correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('[Example](https://example.com)');

        expect($html)->toContain('href="https://example.com"');
        expect($html)->toContain('>Example</a>');
        $this->assertMarkdownContainsLink($html, 'https://example.com', 'Example');
    });

    it('parses images correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('![Alt text](https://example.com/image.jpg)');

        expect($html)->toContain('<img');
        expect($html)->toContain('src="https://example.com/image.jpg"');
        expect($html)->toContain('alt="Alt text"');
        $this->assertMarkdownContainsImage($html, 'https://example.com/image.jpg', 'Alt text');
    });

    it('parses blockquotes correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('> This is a quote.');

        expect($html)->toContain('<blockquote>');
        $this->assertMarkdownContainsBlockquote($html);
    });

    it('parses unordered lists correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse("- Item 1\n- Item 2\n- Item 3");

        expect($html)->toContain('<ul>');
        expect($html)->toContain('<li>Item 1</li>');
        expect($html)->toContain('<li>Item 2</li>');
        expect($html)->toContain('<li>Item 3</li>');
        $this->assertMarkdownContainsList($html, 'ul');
    });

    it('parses ordered lists correctly', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse("1. First\n2. Second\n3. Third");

        expect($html)->toContain('<ol>');
        expect($html)->toContain('<li>First</li>');
        $this->assertMarkdownContainsList($html, 'ol');
    });

    it('parses horizontal rules', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse("Text above\n\n---\n\nText below");

        expect($html)->toContain('<hr');
    });

    it('handles empty content', function () {
        $parser = ParserFactory::create();
        $html = $parser->parse('');

        expect($html)->toBe('');
    });

    it('parses complex markdown with multiple features', function () {
        $parser = ParserFactory::create();
        $markdown = MarkdownFixtures::complex();
        $html = $parser->parse($markdown);

        $this->assertMarkdownContainsHeadingLevel($html, 1);
        $this->assertMarkdownContainsHeadingLevel($html, 2);
        $this->assertMarkdownContainsBoldText($html);
        $this->assertMarkdownContainsItalicText($html);
        $this->assertMarkdownContainsCodeBlock($html, 'php');
        $this->assertMarkdownContainsList($html, 'ul');
        $this->assertMarkdownContainsBlockquote($html);
        $this->assertMarkdownContainsLink($html, 'https://example.com');
        $this->assertMarkdownContainsTable($html);
    });
});

describe('ParserFactory with ParsedownExtra features', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('parses tables correctly', function () {
        $parser = ParserFactory::create();
        $markdown = MarkdownFixtures::withTables();
        $html = $parser->parse($markdown);

        expect($html)->toContain('<table>');
        $this->assertMarkdownContainsTable($html);
    });

    it('parses footnotes', function () {
        $parser = ParserFactory::create();
        $markdown = "Text with footnote[^1].\n\n[^1]: Footnote content.";
        $html = $parser->parse($markdown);

        // Footnotes should create sup elements and footnote section.
        expect($html)->toContain('footnote');
    });

    it('parses definition lists', function () {
        $parser = ParserFactory::create();
        $markdown = "Term\n:   Definition";
        $html = $parser->parse($markdown);

        expect($html)->toContain('<dl>');
        expect($html)->toContain('<dt>Term</dt>');
        expect($html)->toContain('<dd>Definition</dd>');
    });
});
