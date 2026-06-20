<?php
/**
 * ParsedownExtraWrapper Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Parser
 */

declare(strict_types=1);

use CrispyTheme\Parser\ParsedownExtraWrapper;
use CrispyTheme\Parser\ParserInterface;

describe('ParsedownExtraWrapper', function () {
    it('implements ParserInterface', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());

        expect($wrapper)->toBeInstanceOf(ParserInterface::class);
    });

    it('parses basic heading markdown to HTML', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('# Hello World');

        expect($html)->toContain('<h1>Hello World</h1>');
    });

    it('parses bold text', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('This is **bold** text.');

        expect($html)->toContain('<strong>bold</strong>');
    });

    it('parses italic text', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('This is *italic* text.');

        expect($html)->toContain('<em>italic</em>');
    });

    it('parses inline code', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('Use `print()` function.');

        expect($html)->toContain('<code>print()</code>');
    });

    it('parses links', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('[Example](https://example.com)');

        expect($html)->toContain('href="https://example.com"');
        expect($html)->toContain('>Example</a>');
    });

    it('parses images', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('![Alt text](https://example.com/img.jpg)');

        expect($html)->toContain('<img');
        expect($html)->toContain('src="https://example.com/img.jpg"');
        expect($html)->toContain('alt="Alt text"');
    });

    it('parses blockquotes', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('> This is a quote.');

        expect($html)->toContain('<blockquote>');
    });

    it('parses unordered lists', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse("- Item 1\n- Item 2");

        expect($html)->toContain('<ul>');
        expect($html)->toContain('<li>Item 1</li>');
    });

    it('parses ordered lists', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse("1. First\n2. Second");

        expect($html)->toContain('<ol>');
        expect($html)->toContain('<li>First</li>');
    });

    it('returns empty string for empty input', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('');

        expect($html)->toBe('');
    });

    it('parses fenced code block with language', function () {
        $wrapper  = new ParsedownExtraWrapper(new ParsedownExtra());
        $markdown = "```php\n<?php echo 'hi';\n```";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('<code');
        expect($html)->toContain('language-php');
    });

    it('parses tables (Extra feature)', function () {
        $wrapper  = new ParsedownExtraWrapper(new ParsedownExtra());
        $markdown = "| Col A | Col B |\n|-------|-------|\n| 1     | 2     |";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('<table>');
        expect($html)->toContain('<th>Col A</th>');
    });

    it('parses footnotes (Extra feature)', function () {
        $wrapper  = new ParsedownExtraWrapper(new ParsedownExtra());
        $markdown = "Text with footnote[^1].\n\n[^1]: Footnote content.";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('footnote');
    });

    it('parses definition lists (Extra feature)', function () {
        $wrapper  = new ParsedownExtraWrapper(new ParsedownExtra());
        $markdown = "Term\n:   Definition";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('<dl>');
        expect($html)->toContain('<dt>Term</dt>');
        expect($html)->toContain('<dd>Definition</dd>');
    });

    it('parses abbreviations (Extra feature)', function () {
        $wrapper  = new ParsedownExtraWrapper(new ParsedownExtra());
        $markdown = "This is HTML.\n\n*[HTML]: Hyper Text Markup Language";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('<abbr');
        expect($html)->toContain('HTML');
    });

    it('allows unsafe HTML by default', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());

        expect($wrapper->allows_unsafe_html())->toBeTrue();
    });

    it('set_allow_unsafe_html changes the setting to false', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $wrapper->set_allow_unsafe_html(false);

        expect($wrapper->allows_unsafe_html())->toBeFalse();
    });

    it('set_allow_unsafe_html can be toggled back to true', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $wrapper->set_allow_unsafe_html(false);
        $wrapper->set_allow_unsafe_html(true);

        expect($wrapper->allows_unsafe_html())->toBeTrue();
    });

    it('parse applies safe mode when unsafe HTML is disallowed', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $wrapper->set_allow_unsafe_html(false);

        $html = $wrapper->parse('<script>alert("xss")</script>');

        expect($html)->not->toContain('<script>');
    });

    it('parse allows raw HTML when unsafe HTML is allowed (default)', function () {
        $wrapper = new ParsedownExtraWrapper(new ParsedownExtra());
        $html    = $wrapper->parse('<em>raw HTML</em>');

        expect($html)->toContain('<em>raw HTML</em>');
    });
});
