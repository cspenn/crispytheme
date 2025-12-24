<?php
/**
 * Base Test Case
 *
 * @package CrispyTheme\Tests
 */

declare(strict_types=1);

namespace CrispyTheme\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use CrispyTheme\Tests\Concerns\MocksTransients;
use CrispyTheme\Tests\Concerns\MocksWordPressFunctions;
use CrispyTheme\Tests\Concerns\MocksWordPressHooks;
use CrispyTheme\Tests\Concerns\AssertsMarkdown;
use CrispyTheme\Tests\Concerns\AssertsCacheBehavior;
use CrispyTheme\Tests\Concerns\AssertsHooks;
use CrispyTheme\Tests\Support\TestingUtility;
use CrispyTheme\Tests\Factories\PostFactory;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;

/**
 * Base test case for CrispyTheme tests.
 *
 * This class provides access to all testing utilities via traits.
 * Individual tests can use only the traits they need, or rely on
 * the base class having them all available.
 */
abstract class TestCase extends BaseTestCase
{
    // Include all concern traits for convenience.
    // Individual tests can override setup to only use what they need.
    use MocksTransients;
    use MocksWordPressFunctions;
    use MocksWordPressHooks;
    use AssertsMarkdown;
    use AssertsCacheBehavior;
    use AssertsHooks;

    /**
     * Temporary files created during tests.
     *
     * @var array<string>
     */
    protected array $tempFiles = [];

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        TestingUtility::setup();
        $this->resetWordPressState();
    }

    /**
     * Tear down the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->cleanupTempFiles();
        $this->resetWordPressState();
        TestingUtility::teardown();

        parent::tearDown();
    }

    /**
     * Reset WordPress global state.
     *
     * @return void
     */
    protected function resetWordPressState(): void
    {
        // Reset global post.
        global $post;
        $post = null;

        // Clear post meta storage.
        if (isset($this->postMeta)) {
            $this->postMeta = [];
        }
    }

    /**
     * Create a mock WordPress post.
     *
     * @param array<string, mixed> $args Post arguments.
     * @return \stdClass Mock post object.
     */
    protected function createMockPost(array $args = []): \stdClass
    {
        return PostFactory::new()->create($args);
    }

    /**
     * Get sample markdown content.
     *
     * @return string Sample markdown.
     */
    protected function getSampleMarkdown(): string
    {
        return MarkdownFixtures::sample();
    }

    /**
     * Assert that a string contains valid HTML.
     *
     * @param string $html HTML string to validate.
     * @return void
     */
    protected function assertValidHtml(string $html): void
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $result = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $this->assertTrue($result, 'HTML should be valid');
    }

    /**
     * Assert that rendered content contains expected elements.
     *
     * @param string $html    Rendered HTML content.
     * @param string $element HTML element to check for.
     * @return void
     */
    protected function assertContainsHtmlElement(string $html, string $element): void
    {
        $pattern = sprintf('/<%s[^>]*>/i', preg_quote($element, '/'));
        $this->assertMatchesRegularExpression($pattern, $html, "HTML should contain <{$element}> element");
    }

    /**
     * Assert that content has expected CSS class.
     *
     * @param string $html  HTML content.
     * @param string $class CSS class to check for.
     * @return void
     */
    protected function assertHasCssClass(string $html, string $class): void
    {
        $pattern = sprintf('/class="[^"]*\b%s\b[^"]*"/i', preg_quote($class, '/'));
        $this->assertMatchesRegularExpression($pattern, $html, "HTML should have CSS class '{$class}'");
    }

    /**
     * Create a temporary file with content.
     *
     * @param string $content File content.
     * @param string $suffix  File suffix.
     * @return string File path.
     */
    protected function createTempFile(string $content, string $suffix = '.md'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'crispy_test_');
        $pathWithSuffix = $path . $suffix;
        rename($path, $pathWithSuffix);
        file_put_contents($pathWithSuffix, $content);

        $this->tempFiles[] = $pathWithSuffix;

        return $pathWithSuffix;
    }

    /**
     * Clean up temporary files.
     *
     * @return void
     */
    protected function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
    }

    /**
     * Remove a specific temporary file.
     *
     * @param string $path File path to remove.
     * @return void
     */
    protected function removeTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }

        $this->tempFiles = array_filter(
            $this->tempFiles,
            static fn(string $file): bool => $file !== $path
        );
    }

    /**
     * Mock a WordPress function.
     *
     * @param string   $function Function name.
     * @param callable $callback Mock callback.
     * @return void
     */
    protected function mockFunction(string $function, callable $callback): void
    {
        if (!function_exists('Brain\Monkey\Functions\when')) {
            $this->markTestSkipped('Brain Monkey is required for function mocking');
        }

        \Brain\Monkey\Functions\when($function)->alias($callback);
    }

    /**
     * Set up minimal WordPress function mocks for basic tests.
     *
     * @return void
     */
    protected function setupMinimalWordPress(): void
    {
        $this->mockEscapeFunctions();
        $this->mockTranslationFunctions();
    }

    /**
     * Set up full WordPress function mocks.
     *
     * @return void
     */
    protected function setupFullWordPress(): void
    {
        $this->setupWordPressFunctions();
        $this->setupTransients();
        $this->setupHooks();
        $this->mockApplyFiltersPassthrough();
        $this->mockDoAction();
    }
}
