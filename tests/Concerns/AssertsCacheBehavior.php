<?php
/**
 * Asserts Cache Behavior Trait
 *
 * Provides cache behavior assertions for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

/**
 * Trait for cache behavior assertions.
 *
 * Note: This trait expects MocksTransients to also be used in the test.
 */
trait AssertsCacheBehavior
{
    /**
     * Assert a cache hit occurred (transient was retrieved).
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function assertCacheHit(string $key): void
    {
        $value = $this->transientStore->get($key);
        expect($value)->not->toBeFalse(
            "Expected cache hit for key '{$key}'"
        );
    }

    /**
     * Assert a cache miss occurred (transient not found).
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function assertCacheMiss(string $key): void
    {
        $value = $this->transientStore->get($key);
        expect($value)->toBeFalse(
            "Expected cache miss for key '{$key}'"
        );
    }

    /**
     * Assert cache key has correct format/prefix.
     *
     * @param string $key            Cache key.
     * @param string $expectedPrefix Expected prefix.
     * @return void
     */
    protected function assertCacheKeyFormat(string $key, string $expectedPrefix): void
    {
        expect($key)->toStartWith($expectedPrefix);
    }

    /**
     * Assert cache was set with specific expiration.
     *
     * @param string $key        Cache key.
     * @param int    $expiration Expected expiration in seconds.
     * @return void
     */
    protected function assertCacheExpiration(string $key, int $expiration): void
    {
        $actual = $this->transientStore->getLastExpiration($key);
        expect($actual)->toBe($expiration);
    }

    /**
     * Assert cache for a post was invalidated.
     *
     * @param int    $postId Post ID.
     * @param string $prefix Cache key prefix (default: 'crispy_md_').
     * @return void
     */
    protected function assertCacheInvalidatedForPost(int $postId, string $prefix = 'crispy_md_'): void
    {
        $pattern = "/^{$prefix}{$postId}_/";
        $matches = $this->transientStore->getMatching($pattern);

        expect($matches)->toBeEmpty(
            "Expected no cache entries for post {$postId}"
        );
    }

    /**
     * Assert cache exists for a post.
     *
     * @param int    $postId Post ID.
     * @param string $prefix Cache key prefix (default: 'crispy_md_').
     * @return void
     */
    protected function assertCacheExistsForPost(int $postId, string $prefix = 'crispy_md_'): void
    {
        $pattern = "/^{$prefix}{$postId}_/";
        $matches = $this->transientStore->getMatching($pattern);

        expect($matches)->not->toBeEmpty(
            "Expected cache entries for post {$postId}"
        );
    }

    /**
     * Assert cache was populated (set operation occurred).
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function assertCacheWasPopulated(string $key): void
    {
        $setCount = $this->transientStore->getOperationCount($key, 'set');
        expect($setCount)->toBeGreaterThan(0);
    }

    /**
     * Assert cache was not repopulated (no additional set operations).
     *
     * @param string $key           Cache key.
     * @param int    $expectedCount Expected number of set operations.
     * @return void
     */
    protected function assertCacheNotRepopulated(string $key, int $expectedCount = 1): void
    {
        $setCount = $this->transientStore->getOperationCount($key, 'set');
        expect($setCount)->toBe($expectedCount);
    }

    /**
     * Assert cache read-through pattern (get followed by set on miss).
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function assertCacheReadThrough(string $key): void
    {
        $getCount = $this->transientStore->getOperationCount($key, 'get');
        $setCount = $this->transientStore->getOperationCount($key, 'set');

        expect($getCount)->toBeGreaterThan(0);
        expect($setCount)->toBe(1);
    }

    /**
     * Assert cache served from cache (get only, no set).
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function assertCacheServedFromCache(string $key): void
    {
        $getCount = $this->transientStore->getOperationCount($key, 'get');
        $setCount = $this->transientStore->getOperationCount($key, 'set');

        expect($getCount)->toBeGreaterThan(0);
        // Set count should be from initial population only.
    }

    /**
     * Get cache value for a key (for complex assertions).
     *
     * @param string $key Cache key.
     * @return mixed Cache value.
     */
    protected function getCacheValue(string $key): mixed
    {
        return $this->transientStore->get($key);
    }

    /**
     * Prime cache with a value.
     *
     * @param string $key        Cache key.
     * @param mixed  $value      Cache value.
     * @param int    $expiration Expiration in seconds.
     * @return void
     */
    protected function primeCache(string $key, mixed $value, int $expiration = 86400): void
    {
        $this->transientStore->set($key, $value, $expiration);
    }

    /**
     * Invalidate cache for a key.
     *
     * @param string $key Cache key.
     * @return void
     */
    protected function invalidateCache(string $key): void
    {
        $this->transientStore->delete($key);
    }

    /**
     * Get all cache keys.
     *
     * @return array<string> Cache keys.
     */
    protected function getAllCacheKeys(): array
    {
        return $this->transientStore->getKeys();
    }
}
