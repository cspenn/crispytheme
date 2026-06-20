<?php
/**
 * ParsedownWrapper Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Parser
 */

declare(strict_types=1);

use CrispyTheme\Parser\ParsedownWrapper;
use CrispyTheme\Parser\ParserInterface;

describe('ParsedownWrapper', function () {
    it('implements ParserInterface', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());

        expect($wrapper)->toBeInstanceOf(ParserInterface::class);
    });

    it('parses basic heading markdown to HTML', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('# Hello World');

        expect($html)->toContain('<h1>Hello World</h1>');
    });

    it('parses bold text', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('This is **bold** text.');

        expect($html)->toContain('<strong>bold</strong>');
    });

    it('parses italic text', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('This is *italic* text.');

        expect($html)->toContain('<em>italic</em>');
    });

    it('parses inline code', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('Use `print()` function.');

        expect($html)->toContain('<code>print()</code>');
    });

    it('parses links', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('[Example](https://example.com)');

        expect($html)->toContain('href="https://example.com"');
        expect($html)->toContain('>Example</a>');
    });

    it('parses images', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('![Alt text](https://example.com/img.jpg)');

        expect($html)->toContain('<img');
        expect($html)->toContain('src="https://example.com/img.jpg"');
        expect($html)->toContain('alt="Alt text"');
    });

    it('parses blockquotes', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('> This is a quote.');

        expect($html)->toContain('<blockquote>');
    });

    it('parses unordered lists', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse("- Item 1\n- Item 2");

        expect($html)->toContain('<ul>');
        expect($html)->toContain('<li>Item 1</li>');
    });

    it('parses ordered lists', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse("1. First\n2. Second");

        expect($html)->toContain('<ol>');
        expect($html)->toContain('<li>First</li>');
    });

    it('returns empty string for empty input', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse('');

        expect($html)->toBe('');
    });

    it('parses fenced code block with language', function () {
        $wrapper  = new ParsedownWrapper(new Parsedown());
        $markdown = "```php\n<?php echo 'hi';\n```";
        $html     = $wrapper->parse($markdown);

        expect($html)->toContain('<code');
        expect($html)->toContain('language-php');
    });

    it('parses horizontal rule', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $html    = $wrapper->parse("Above\n\n---\n\nBelow");

        expect($html)->toContain('<hr');
    });

    it('allows unsafe HTML by default', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());

        expect($wrapper->allows_unsafe_html())->toBeTrue();
    });

    it('set_allow_unsafe_html changes the setting to false', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $wrapper->set_allow_unsafe_html(false);

        expect($wrapper->allows_unsafe_html())->toBeFalse();
    });

    it('set_allow_unsafe_html can be toggled back to true', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $wrapper->set_allow_unsafe_html(false);
        $wrapper->set_allow_unsafe_html(true);

        expect($wrapper->allows_unsafe_html())->toBeTrue();
    });

    it('parse applies safe mode when unsafe HTML is disallowed', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        $wrapper->set_allow_unsafe_html(false);

        // In safe mode, raw HTML is escaped — the <script> tag should not pass through.
        $html = $wrapper->parse('<script>alert("xss")</script>');

        expect($html)->not->toContain('<script>');
    });

    it('parse allows raw HTML when unsafe HTML is allowed (default)', function () {
        $wrapper = new ParsedownWrapper(new Parsedown());
        // Default: allow_unsafe_html = true, so setSafeMode(false).
        $html = $wrapper->parse('<em>raw HTML</em>');

        expect($html)->toContain('<em>raw HTML</em>');
    });
});
