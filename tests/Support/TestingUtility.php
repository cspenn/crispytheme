<?php
/**
 * Testing Utility Facade
 *
 * Central entry point for all testing utilities.
 *
 * @package CrispyTheme\Tests\Support
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Support;

use CrispyTheme\Tests\Factories\PostFactory;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;
use CrispyTheme\Tests\Fixtures\PostFixtures;
use CrispyTheme\Tests\Fixtures\CacheStateFixtures;

/**
 * Central testing utility facade.
 */
class TestingUtility
{
    /**
     * Transient store instance.
     */
    private static ?TransientStore $transientStore = null;

    /**
     * Hook registry instance.
     */
    private static ?HookRegistry $hookRegistry = null;

    /**
     * Markdown asserter instance.
     */
    private static ?MarkdownAsserter $markdownAsserter = null;

    /**
     * Post factory instance.
     */
    private static ?PostFactory $postFactory = null;

    /**
     * Set up testing utilities.
     *
     * @return void
     */
    public static function setup(): void
    {
        self::$transientStore   = new TransientStore();
        self::$hookRegistry     = new HookRegistry();
        self::$markdownAsserter = new MarkdownAsserter();
        self::$postFactory      = new PostFactory();
    }

    /**
     * Tear down testing utilities.
     *
     * @return void
     */
    public static function teardown(): void
    {
        if (self::$transientStore !== null) {
            self::$transientStore->clear();
        }

        if (self::$hookRegistry !== null) {
            self::$hookRegistry->reset();
        }

        self::$transientStore   = null;
        self::$hookRegistry     = null;
        self::$markdownAsserter = null;
        self::$postFactory      = null;

        // Reset global post.
        global $post;
        $post = null;
    }

    /**
     * Get the transient store.
     *
     * @return TransientStore
     */
    public static function transients(): TransientStore
    {
        if (self::$transientStore === null) {
            self::$transientStore = new TransientStore();
        }

        return self::$transientStore;
    }

    /**
     * Get the hook registry.
     *
     * @return HookRegistry
     */
    public static function hooks(): HookRegistry
    {
        if (self::$hookRegistry === null) {
            self::$hookRegistry = new HookRegistry();
        }

        return self::$hookRegistry;
    }

    /**
     * Get the markdown asserter.
     *
     * @return MarkdownAsserter
     */
    public static function markdown(): MarkdownAsserter
    {
        if (self::$markdownAsserter === null) {
            self::$markdownAsserter = new MarkdownAsserter();
        }

        return self::$markdownAsserter;
    }

    /**
     * Get the post factory.
     *
     * @return PostFactory
     */
    public static function posts(): PostFactory
    {
        if (self::$postFactory === null) {
            self::$postFactory = new PostFactory();
        }

        return self::$postFactory;
    }

    /**
     * Create a mock post (convenience method).
     *
     * @param array<string, mixed> $attributes Post attributes.
     * @return \stdClass Mock post object.
     */
    public static function createPost(array $attributes = []): \stdClass
    {
        return self::posts()->create($attributes);
    }

    /**
     * Get markdown fixtures class.
     *
     * @return class-string<MarkdownFixtures>
     */
    public static function markdownFixtures(): string
    {
        return MarkdownFixtures::class;
    }

    /**
     * Get post fixtures class.
     *
     * @return class-string<PostFixtures>
     */
    public static function postFixtures(): string
    {
        return PostFixtures::class;
    }
}
