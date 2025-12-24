<?php
/**
 * Transient Cache Unit Tests
 *
 * @package CrispyTheme\Tests\Unit
 */

declare(strict_types=1);

use CrispyTheme\Cache\TransientCache;
use Brain\Monkey\Functions;

describe('TransientCache', function () {
    beforeEach(function () {
        // Use the MocksTransients trait from TestCase.
        $this->setupTransients();

        // Mock apply_filters to return the value as-is.
        Functions\when('apply_filters')->returnArg(2);
    });

    it('generates cache key correctly', function () {
        $cache = new TransientCache();

        $key = $cache->generate_key(1, 'test content');

        expect($key)->toBeString();
        expect(strlen($key))->toBeLessThanOrEqual(172); // WordPress transient key limit.
    });

    it('generates different keys for different post IDs', function () {
        $cache = new TransientCache();

        $key1 = $cache->generate_key(1, 'content');
        $key2 = $cache->generate_key(2, 'content');

        expect($key1)->not->toBe($key2);
    });

    it('generates different keys for different content', function () {
        $cache = new TransientCache();

        $key1 = $cache->generate_key(1, 'content A');
        $key2 = $cache->generate_key(1, 'content B');

        expect($key1)->not->toBe($key2);
    });

    it('generates same key for same inputs', function () {
        $cache = new TransientCache();

        $key1 = $cache->generate_key(1, 'content');
        $key2 = $cache->generate_key(1, 'content');

        expect($key1)->toBe($key2);
    });

    it('can store and retrieve values', function () {
        $cache = new TransientCache();

        $cache->set('test_key', 'test_value');
        $value = $cache->get('test_key');

        expect($value)->toBe('test_value');
    });

    it('returns false for non-existent keys', function () {
        $cache = new TransientCache();

        $value = $cache->get('non_existent_key');

        expect($value)->toBeFalse();
    });

    it('can delete values', function () {
        $cache = new TransientCache();

        $cache->set('test_key', 'test_value');
        $result = $cache->delete('test_key');
        $value = $cache->get('test_key');

        expect($result)->toBeTrue();
        expect($value)->toBeFalse();
    });

    it('uses correct prefix for cache keys', function () {
        $cache = new TransientCache();
        $key = $cache->generate_key(1, 'content');

        expect($key)->toStartWith('crispy_md_');
    });

    it('includes post ID in cache key', function () {
        $cache = new TransientCache();
        $key = $cache->generate_key(42, 'content');

        expect($key)->toContain('42');
    });
});

describe('TransientCache with filters', function () {
    beforeEach(function () {
        // Use the MocksTransients trait from TestCase.
        $this->setupTransients();
        $this->lastExpiration = 0;

        // Override set_transient to capture expiration.
        Functions\when('set_transient')->alias(function (string $name, $value, int $expiration = 0) {
            $this->transientStore->set($name, $value, $expiration);
            $this->lastExpiration = $expiration;
            return true;
        });
    });

    it('uses default expiration when no filter modifies it', function () {
        // Return the default value (86400 = 1 day).
        Functions\when('apply_filters')->returnArg(2);

        $cache = new TransientCache();
        $cache->set('test_key', 'test_value');

        expect($this->lastExpiration)->toBe(86400);
    });

    it('respects custom expiration from filter', function () {
        // Return a custom value from the filter.
        Functions\when('apply_filters')->justReturn(3600); // 1 hour

        $cache = new TransientCache();
        $cache->set('test_key', 'test_value');

        expect($this->lastExpiration)->toBe(3600);
    });

    it('uses explicit expiration when provided', function () {
        Functions\when('apply_filters')->returnArg(2);

        $cache = new TransientCache();
        $cache->set('test_key', 'test_value', 7200); // 2 hours

        expect($this->lastExpiration)->toBe(7200);
    });
});
