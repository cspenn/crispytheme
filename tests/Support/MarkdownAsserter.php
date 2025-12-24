<?php
/**
 * Markdown Asserter
 *
 * Engine for markdown-specific HTML assertions.
 *
 * @package CrispyTheme\Tests\Support
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Support;

use DOMDocument;
use DOMXPath;

/**
 * Engine for markdown-specific assertions.
 */
class MarkdownAsserter
{
    /**
     * Check if HTML contains a heading with specific level and text.
     *
     * @param string $html  HTML content.
     * @param int    $level Heading level (1-6).
     * @param string $text  Expected heading text.
     * @return bool True if found.
     */
    public function hasHeading(string $html, int $level, string $text): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        $headings = $dom->getElementsByTagName("h{$level}");
        foreach ($headings as $heading) {
            if (trim($heading->textContent) === $text) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if HTML contains any heading with specific level.
     *
     * @param string $html  HTML content.
     * @param int    $level Heading level (1-6).
     * @return bool True if found.
     */
    public function hasHeadingLevel(string $html, int $level): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        return $dom->getElementsByTagName("h{$level}")->length > 0;
    }

    /**
     * Check if HTML contains a code block.
     *
     * @param string      $html     HTML content.
     * @param string|null $language Optional language class (e.g., 'php', 'javascript').
     * @return bool True if found.
     */
    public function hasCodeBlock(string $html, ?string $language = null): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        $xpath = new DOMXPath($dom);

        // Look for <pre><code> structure.
        $codeBlocks = $xpath->query('//pre/code');
        if ($codeBlocks === false || $codeBlocks->length === 0) {
            return false;
        }

        if ($language === null) {
            return true;
        }

        // Check for language class.
        foreach ($codeBlocks as $code) {
            $class = $code->getAttribute('class');
            if (str_contains($class, "language-{$language}") || str_contains($class, $language)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if HTML contains a table.
     *
     * @param string $html HTML content.
     * @return bool True if found.
     */
    public function hasTable(string $html): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        return $dom->getElementsByTagName('table')->length > 0;
    }

    /**
     * Check if HTML contains a link with specific href.
     *
     * @param string      $html HTML content.
     * @param string      $href Expected href value.
     * @param string|null $text Optional link text.
     * @return bool True if found.
     */
    public function hasLink(string $html, string $href, ?string $text = null): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            if ($link->getAttribute('href') === $href) {
                if ($text === null || trim($link->textContent) === $text) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if HTML contains an image with specific src.
     *
     * @param string      $html HTML content.
     * @param string      $src  Expected src value.
     * @param string|null $alt  Optional alt text.
     * @return bool True if found.
     */
    public function hasImage(string $html, string $src, ?string $alt = null): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            if ($image->getAttribute('src') === $src) {
                if ($alt === null || $image->getAttribute('alt') === $alt) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if HTML contains a list of specific type.
     *
     * @param string $html HTML content.
     * @param string $type List type ('ul' or 'ol').
     * @return bool True if found.
     */
    public function hasList(string $html, string $type = 'ul'): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        return $dom->getElementsByTagName($type)->length > 0;
    }

    /**
     * Check if HTML contains a blockquote.
     *
     * @param string $html HTML content.
     * @return bool True if found.
     */
    public function hasBlockquote(string $html): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        return $dom->getElementsByTagName('blockquote')->length > 0;
    }

    /**
     * Count elements of a specific tag.
     *
     * @param string $html HTML content.
     * @param string $tag  Tag name.
     * @return int Element count.
     */
    public function countElements(string $html, string $tag): int
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return 0;
        }

        return $dom->getElementsByTagName($tag)->length;
    }

    /**
     * Check if HTML contains a CSS class.
     *
     * @param string $html  HTML content.
     * @param string $class CSS class.
     * @return bool True if found.
     */
    public function hasCssClass(string $html, string $class): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]");

        return $nodes !== false && $nodes->length > 0;
    }

    /**
     * Check if HTML contains strong/bold text.
     *
     * @param string      $html HTML content.
     * @param string|null $text Optional specific text.
     * @return bool True if found.
     */
    public function hasBoldText(string $html, ?string $text = null): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        foreach (['strong', 'b'] as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            if ($elements->length > 0) {
                if ($text === null) {
                    return true;
                }
                foreach ($elements as $element) {
                    if (str_contains($element->textContent, $text)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if HTML contains em/italic text.
     *
     * @param string      $html HTML content.
     * @param string|null $text Optional specific text.
     * @return bool True if found.
     */
    public function hasItalicText(string $html, ?string $text = null): bool
    {
        $dom = $this->createDom($html);
        if ($dom === null) {
            return false;
        }

        foreach (['em', 'i'] as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            if ($elements->length > 0) {
                if ($text === null) {
                    return true;
                }
                foreach ($elements as $element) {
                    if (str_contains($element->textContent, $text)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if text was properly stripped of markdown formatting.
     *
     * @param string $text Text to check.
     * @return bool True if no markdown formatting present.
     */
    public function isStrippedOfFormatting(string $text): bool
    {
        $markdownPatterns = [
            '/\*\*[^*]+\*\*/',      // Bold.
            '/\*[^*]+\*/',          // Italic.
            '/\[[^\]]+\]\([^)]+\)/', // Links.
            '/^#+\s/m',              // Headers.
            '/`[^`]+`/',             // Inline code.
            '/```[\s\S]*?```/',      // Code blocks.
        ];

        foreach ($markdownPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a DOMDocument from HTML string.
     *
     * @param string $html HTML content.
     * @return DOMDocument|null DOM document or null on failure.
     */
    private function createDom(string $html): ?DOMDocument
    {
        if (empty($html)) {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Wrap in a container for proper parsing.
        $wrapped = '<div>' . $html . '</div>';
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        return $dom;
    }
}
