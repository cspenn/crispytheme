<?php
/**
 * Asserts Markdown Trait
 *
 * Provides markdown-specific assertions for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

use CrispyTheme\Tests\Support\MarkdownAsserter;

/**
 * Trait for markdown-specific assertions.
 */
trait AssertsMarkdown
{
    /**
     * Markdown asserter instance.
     */
    protected MarkdownAsserter $markdownAsserter;

    /**
     * Set up markdown assertions.
     *
     * @return void
     */
    protected function setupMarkdownAssertions(): void
    {
        $this->markdownAsserter = new MarkdownAsserter();
    }

    /**
     * Get or create markdown asserter.
     *
     * @return MarkdownAsserter
     */
    protected function getMarkdownAsserter(): MarkdownAsserter
    {
        if (!isset($this->markdownAsserter)) {
            $this->markdownAsserter = new MarkdownAsserter();
        }
        return $this->markdownAsserter;
    }

    /**
     * Assert HTML contains a heading with specific level and text.
     *
     * @param string $html  HTML content.
     * @param int    $level Heading level (1-6).
     * @param string $text  Expected heading text.
     * @return void
     */
    protected function assertMarkdownContainsHeading(string $html, int $level, string $text): void
    {
        expect($this->getMarkdownAsserter()->hasHeading($html, $level, $text))->toBeTrue(
            "Expected HTML to contain <h{$level}>{$text}</h{$level}>"
        );
    }

    /**
     * Assert HTML contains a heading of specific level.
     *
     * @param string $html  HTML content.
     * @param int    $level Heading level (1-6).
     * @return void
     */
    protected function assertMarkdownContainsHeadingLevel(string $html, int $level): void
    {
        expect($this->getMarkdownAsserter()->hasHeadingLevel($html, $level))->toBeTrue(
            "Expected HTML to contain <h{$level}> element"
        );
    }

    /**
     * Assert HTML contains a code block.
     *
     * @param string      $html     HTML content.
     * @param string|null $language Optional language class.
     * @return void
     */
    protected function assertMarkdownContainsCodeBlock(string $html, ?string $language = null): void
    {
        $message = $language !== null
            ? "Expected HTML to contain code block with language '{$language}'"
            : 'Expected HTML to contain code block';

        expect($this->getMarkdownAsserter()->hasCodeBlock($html, $language))->toBeTrue($message);
    }

    /**
     * Assert HTML does not contain a code block.
     *
     * @param string $html HTML content.
     * @return void
     */
    protected function assertMarkdownNotContainsCodeBlock(string $html): void
    {
        expect($this->getMarkdownAsserter()->hasCodeBlock($html))->toBeFalse(
            'Expected HTML to not contain code block'
        );
    }

    /**
     * Assert HTML contains a table.
     *
     * @param string $html HTML content.
     * @return void
     */
    protected function assertMarkdownContainsTable(string $html): void
    {
        expect($this->getMarkdownAsserter()->hasTable($html))->toBeTrue(
            'Expected HTML to contain table'
        );
    }

    /**
     * Assert HTML contains a link.
     *
     * @param string      $html HTML content.
     * @param string      $href Expected href value.
     * @param string|null $text Optional link text.
     * @return void
     */
    protected function assertMarkdownContainsLink(string $html, string $href, ?string $text = null): void
    {
        $message = $text !== null
            ? "Expected HTML to contain link to '{$href}' with text '{$text}'"
            : "Expected HTML to contain link to '{$href}'";

        expect($this->getMarkdownAsserter()->hasLink($html, $href, $text))->toBeTrue($message);
    }

    /**
     * Assert HTML contains an image.
     *
     * @param string      $html HTML content.
     * @param string      $src  Expected src value.
     * @param string|null $alt  Optional alt text.
     * @return void
     */
    protected function assertMarkdownContainsImage(string $html, string $src, ?string $alt = null): void
    {
        $message = $alt !== null
            ? "Expected HTML to contain image '{$src}' with alt '{$alt}'"
            : "Expected HTML to contain image '{$src}'";

        expect($this->getMarkdownAsserter()->hasImage($html, $src, $alt))->toBeTrue($message);
    }

    /**
     * Assert HTML contains a list.
     *
     * @param string $html HTML content.
     * @param string $type List type ('ul' or 'ol').
     * @return void
     */
    protected function assertMarkdownContainsList(string $html, string $type = 'ul'): void
    {
        expect($this->getMarkdownAsserter()->hasList($html, $type))->toBeTrue(
            "Expected HTML to contain <{$type}> list"
        );
    }

    /**
     * Assert HTML contains a blockquote.
     *
     * @param string $html HTML content.
     * @return void
     */
    protected function assertMarkdownContainsBlockquote(string $html): void
    {
        expect($this->getMarkdownAsserter()->hasBlockquote($html))->toBeTrue(
            'Expected HTML to contain blockquote'
        );
    }

    /**
     * Assert HTML contains bold text.
     *
     * @param string      $html HTML content.
     * @param string|null $text Optional specific text.
     * @return void
     */
    protected function assertMarkdownContainsBoldText(string $html, ?string $text = null): void
    {
        $message = $text !== null
            ? "Expected HTML to contain bold text '{$text}'"
            : 'Expected HTML to contain bold text';

        expect($this->getMarkdownAsserter()->hasBoldText($html, $text))->toBeTrue($message);
    }

    /**
     * Assert HTML contains italic text.
     *
     * @param string      $html HTML content.
     * @param string|null $text Optional specific text.
     * @return void
     */
    protected function assertMarkdownContainsItalicText(string $html, ?string $text = null): void
    {
        $message = $text !== null
            ? "Expected HTML to contain italic text '{$text}'"
            : 'Expected HTML to contain italic text';

        expect($this->getMarkdownAsserter()->hasItalicText($html, $text))->toBeTrue($message);
    }

    /**
     * Assert HTML contains a specific CSS class.
     *
     * @param string $html  HTML content.
     * @param string $class CSS class.
     * @return void
     */
    protected function assertMarkdownHasCssClass(string $html, string $class): void
    {
        expect($this->getMarkdownAsserter()->hasCssClass($html, $class))->toBeTrue(
            "Expected HTML to contain CSS class '{$class}'"
        );
    }

    /**
     * Assert element count.
     *
     * @param string $html  HTML content.
     * @param string $tag   Tag name.
     * @param int    $count Expected count.
     * @return void
     */
    protected function assertMarkdownElementCount(string $html, string $tag, int $count): void
    {
        expect($this->getMarkdownAsserter()->countElements($html, $tag))->toBe($count);
    }

    /**
     * Assert text has been stripped of markdown formatting.
     *
     * @param string $text Text to check.
     * @return void
     */
    protected function assertMarkdownStripped(string $text): void
    {
        expect($this->getMarkdownAsserter()->isStrippedOfFormatting($text))->toBeTrue(
            'Expected text to be stripped of markdown formatting'
        );
    }

    /**
     * Assert text still contains markdown formatting.
     *
     * @param string $text Text to check.
     * @return void
     */
    protected function assertMarkdownNotStripped(string $text): void
    {
        expect($this->getMarkdownAsserter()->isStrippedOfFormatting($text))->toBeFalse(
            'Expected text to still contain markdown formatting'
        );
    }
}
