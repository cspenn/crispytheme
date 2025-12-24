<?php
/**
 * Markdown Fixtures
 *
 * Provides sample markdown content for testing.
 *
 * @package CrispyTheme\Tests\Fixtures
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Fixtures;

/**
 * Markdown content fixtures for testing.
 */
class MarkdownFixtures
{
    /**
     * Get simple markdown with basic paragraph.
     *
     * @return string
     */
    public static function simple(): string
    {
        return <<<'MARKDOWN'
# Sample Heading

This is a simple paragraph with some text.
MARKDOWN;
    }

    /**
     * Get markdown with multiple headings.
     *
     * @return string
     */
    public static function withHeadings(): string
    {
        return <<<'MARKDOWN'
# Heading 1

Some content under heading 1.

## Heading 2

Content under heading 2.

### Heading 3

Content under heading 3.

#### Heading 4

Content under heading 4.
MARKDOWN;
    }

    /**
     * Get markdown with code blocks.
     *
     * @return string
     */
    public static function withCodeBlocks(): string
    {
        return <<<'MARKDOWN'
# Code Examples

Here's some PHP code:

```php
<?php
function hello(): string {
    return "Hello, World!";
}
```

And some JavaScript:

```javascript
function greet(name) {
    return `Hello, ${name}!`;
}
```

Inline code: `const x = 42;`
MARKDOWN;
    }

    /**
     * Get markdown with lists.
     *
     * @return string
     */
    public static function withLists(): string
    {
        return <<<'MARKDOWN'
# Lists

## Unordered List

- Item 1
- Item 2
- Item 3

## Ordered List

1. First item
2. Second item
3. Third item

## Nested List

- Parent 1
  - Child 1.1
  - Child 1.2
- Parent 2
  - Child 2.1
MARKDOWN;
    }

    /**
     * Get markdown with tables.
     *
     * @return string
     */
    public static function withTables(): string
    {
        return <<<'MARKDOWN'
# Data Table

| Name | Age | City |
|------|-----|------|
| Alice | 30 | NYC |
| Bob | 25 | LA |
| Carol | 35 | SF |
MARKDOWN;
    }

    /**
     * Get markdown with links.
     *
     * @return string
     */
    public static function withLinks(): string
    {
        return <<<'MARKDOWN'
# Links

Visit [Example Site](https://example.com) for more info.

Check out [Google](https://google.com "Google Search") as well.

Here's an auto-link: <https://github.com>
MARKDOWN;
    }

    /**
     * Get markdown with images.
     *
     * @return string
     */
    public static function withImages(): string
    {
        return <<<'MARKDOWN'
# Images

![Alt text](https://example.com/image.jpg)

![Logo](https://example.com/logo.png "Company Logo")
MARKDOWN;
    }

    /**
     * Get markdown with emphasis.
     *
     * @return string
     */
    public static function withEmphasis(): string
    {
        return <<<'MARKDOWN'
# Emphasis

This text has **bold** and *italic* formatting.

You can also use __bold__ and _italic_ underscores.

Here's ***bold and italic*** together.
MARKDOWN;
    }

    /**
     * Get markdown with blockquotes.
     *
     * @return string
     */
    public static function withBlockquotes(): string
    {
        return <<<'MARKDOWN'
# Quotes

> This is a blockquote.
> It can span multiple lines.

> Nested quote:
> > This is nested inside.
MARKDOWN;
    }

    /**
     * Get complex markdown with multiple features.
     *
     * @return string
     */
    public static function complex(): string
    {
        return <<<'MARKDOWN'
# Sample Heading

This is a paragraph with **bold** and *italic* text.

## Code Example

```php
<?php
echo "Hello, World!";
```

- List item 1
- List item 2
- List item 3

> This is a blockquote.

[Link text](https://example.com)

| Column 1 | Column 2 |
|----------|----------|
| Data 1   | Data 2   |
MARKDOWN;
    }

    /**
     * Get markdown that matches the original getSampleMarkdown() function.
     *
     * @return string
     */
    public static function sample(): string
    {
        return <<<'MARKDOWN'
# Sample Heading

This is a paragraph with **bold** and *italic* text.

## Code Example

```php
<?php
echo "Hello, World!";
```

- List item 1
- List item 2
- List item 3

> This is a blockquote.

[Link text](https://example.com)
MARKDOWN;
    }

    /**
     * Get edge case: empty string.
     *
     * @return string
     */
    public static function empty(): string
    {
        return '';
    }

    /**
     * Get edge case: whitespace only.
     *
     * @return string
     */
    public static function whitespaceOnly(): string
    {
        return "   \n\n   \t   \n";
    }

    /**
     * Get edge case: very long content.
     *
     * @param int $paragraphs Number of paragraphs.
     * @return string
     */
    public static function veryLong(int $paragraphs = 100): string
    {
        $content = "# Long Document\n\n";

        for ($i = 1; $i <= $paragraphs; ++$i) {
            $content .= "## Section {$i}\n\n";
            $content .= "This is paragraph {$i}. Lorem ipsum dolor sit amet, consectetur adipiscing elit. ";
            $content .= "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\n";
        }

        return $content;
    }

    /**
     * Get edge case: special characters.
     *
     * @return string
     */
    public static function withSpecialCharacters(): string
    {
        return <<<'MARKDOWN'
# Special Characters

HTML entities: &amp; &lt; &gt; &quot;

Unicode: café résumé naïve

Emoji: 🚀 🎉 ✨

Code with special chars: `<div class="test">`
MARKDOWN;
    }

    /**
     * Get edge case: malicious HTML.
     *
     * @return string
     */
    public static function withMaliciousHtml(): string
    {
        return <<<'MARKDOWN'
# Test XSS Prevention

<script>alert('xss')</script>

<img src="x" onerror="alert('xss')">

<a href="javascript:alert('xss')">Click me</a>

<div onclick="alert('xss')">Hover me</div>
MARKDOWN;
    }

    /**
     * Get all edge cases as an array.
     *
     * @return array<string, string>
     */
    public static function edgeCases(): array
    {
        return [
            'empty'              => self::empty(),
            'whitespace_only'    => self::whitespaceOnly(),
            'special_characters' => self::withSpecialCharacters(),
            'malicious_html'     => self::withMaliciousHtml(),
        ];
    }

    /**
     * Get markdown for a specific word count (for excerpt testing).
     *
     * @param int $wordCount Target word count.
     * @return string
     */
    public static function withWordCount(int $wordCount): string
    {
        $words   = [];
        $lorem   = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
        $loremCount = count($lorem);

        for ($i = 0; $i < $wordCount; ++$i) {
            $words[] = $lorem[$i % $loremCount];
        }

        return "# Test Content\n\n" . implode(' ', $words);
    }
}
