<?php
/**
 * Mocks Transients Trait
 *
 * Provides WordPress transient mocking for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

use Brain\Monkey\Functions;
use CrispyTheme\Tests\Support\TransientStore;

/**
 * Trait for WordPress transient mocking.
 */
trait MocksTransients
{
    /**
     * Transient store instance.
     */
    protected TransientStore $transientStore;

    /**
     * Set up transient mocking.
     *
     * @return void
     */
    protected function setupTransients(): void
    {
        $this->transientStore = new TransientStore();

        Functions\when('get_transient')->alias(
            fn(string $name): mixed => $this->transientStore->get($name)
        );

        Functions\when('set_transient')->alias(
            fn(string $name, mixed $value, int $expiration = 0): bool => $this->transientStore->set($name, $value, $expiration)
        );

        Functions\when('delete_transient')->alias(
            fn(string $name): bool => $this->transientStore->delete($name)
        );
    }

    /**
     * Get a transient value.
     *
     * @param string $name Transient name.
     * @return mixed Transient value or false.
     */
    protected function getTransient(string $name): mixed
    {
        return $this->transientStore->get($name);
    }

    /**
     * Set a transient value directly (bypasses mock).
     *
     * @param string $name       Transient name.
     * @param mixed  $value      Transient value.
     * @param int    $expiration Expiration in seconds.
     * @return bool True on success.
     */
    protected function setTransient(string $name, mixed $value, int $expiration = 0): bool
    {
        return $this->transientStore->set($name, $value, $expiration);
    }

    /**
     * Delete a transient directly (bypasses mock).
     *
     * @param string $name Transient name.
     * @return bool True if deleted.
     */
    protected function deleteTransient(string $name): bool
    {
        return $this->transientStore->delete($name);
    }

    /**
     * Assert that a transient exists.
     *
     * @param string $name Transient name.
     * @return void
     */
    protected function assertTransientExists(string $name): void
    {
        expect($this->transientStore->has($name))->toBeTrue(
            "Expected transient '{$name}' to exist"
        );
    }

    /**
     * Assert that a transient does not exist.
     *
     * @param string $name Transient name.
     * @return void
     */
    protected function assertTransientNotExists(string $name): void
    {
        expect($this->transientStore->has($name))->toBeFalse(
            "Expected transient '{$name}' to not exist"
        );
    }

    /**
     * Assert that a transient has a specific value.
     *
     * @param string $name     Transient name.
     * @param mixed  $expected Expected value.
     * @return void
     */
    protected function assertTransientValue(string $name, mixed $expected): void
    {
        expect($this->transientStore->get($name))->toBe($expected);
    }

    /**
     * Assert that a transient was set with specific expiration.
     *
     * @param string $name       Transient name.
     * @param int    $expiration Expected expiration.
     * @return void
     */
    protected function assertTransientExpiration(string $name, int $expiration): void
    {
        expect($this->transientStore->getLastExpiration($name))->toBe($expiration);
    }

    /**
     * Assert the number of get operations for a transient.
     *
     * @param string $name  Transient name.
     * @param int    $count Expected count.
     * @return void
     */
    protected function assertTransientGetCount(string $name, int $count): void
    {
        expect($this->transientStore->getOperationCount($name, 'get'))->toBe($count);
    }

    /**
     * Assert the number of set operations for a transient.
     *
     * @param string $name  Transient name.
     * @param int    $count Expected count.
     * @return void
     */
    protected function assertTransientSetCount(string $name, int $count): void
    {
        expect($this->transientStore->getOperationCount($name, 'set'))->toBe($count);
    }

    /**
     * Clear all transients.
     *
     * @return void
     */
    protected function clearTransients(): void
    {
        $this->transientStore->clear();
    }

    /**
     * Get all transient keys matching a pattern.
     *
     * @param string $pattern Regex pattern.
     * @return array<string, mixed> Matching transients.
     */
    protected function getTransientsMatching(string $pattern): array
    {
        return $this->transientStore->getMatching($pattern);
    }
}
