<?php
/**
 * TransientCache Extended Unit Tests
 *
 * Covers delete_for_post, clear_all, get_stats, and edge cases not covered
 * by the existing tests/Unit/TransientCacheTest.php (which must NOT be modified).
 *
 * @package CrispyTheme\Tests\Unit\Cache
 */

declare(strict_types=1);

use CrispyTheme\Cache\TransientCache;
use Brain\Monkey\Functions;

/**
 * Minimal $wpdb stub whose methods can be called directly.
 *
 * PHP only allows method-call syntax on real objects, not closures stored in
 * public properties, so we need an actual class.
 */
class MockWpdb
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    public string $options = 'wp_options';

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    /** Returns a flat array of option_name values. */
    public function get_col(string $sql): array
    {
        return array_column($this->rows, 'option_name');
    }

    /** Returns an array of stdClass objects with option_name and size. */
    public function get_results(string $sql): array
    {
        return array_map(function (array $row): stdClass {
            $obj              = new stdClass();
            $obj->option_name = $row['option_name'];
            $obj->size        = (int) ($row['size'] ?? 0);
            return $obj;
        }, $this->rows);
    }

    /** Mimics wpdb::prepare() for the single-%s patterns used by TransientCache. */
    public function prepare(string $query, mixed ...$args): string
    {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
}

describe('TransientCache::delete_for_post', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns 0 when no transients match the post ID', function () {
        global $wpdb;
        $wpdb = new MockWpdb([]);

        $cache  = new TransientCache();
        $result = $cache->delete_for_post(99);

        expect($result)->toBe(0);
    });

    it('deletes matching transients and returns the count', function () {
        global $wpdb;

        // Seed two transients for post 1 in the store.
        $cache = new TransientCache();
        $cache->set('crispy_md_1_aabbcc', 'content A');
        $cache->set('crispy_md_1_ddeeff', 'content B');

        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_1_aabbcc'],
            ['option_name' => '_transient_crispy_md_1_ddeeff'],
        ]);

        $result = $cache->delete_for_post(1);

        expect($result)->toBe(2);
        // Both transients should be gone.
        expect($cache->get('crispy_md_1_aabbcc'))->toBeFalse();
        expect($cache->get('crispy_md_1_ddeeff'))->toBeFalse();
    });

    it('only deletes transients for the specified post ID', function () {
        global $wpdb;

        $cache = new TransientCache();
        $cache->set('crispy_md_1_aabbcc', 'content A');
        $cache->set('crispy_md_2_ddeeff', 'content B');

        // Simulate DB only returning the post 1 entry.
        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_1_aabbcc'],
        ]);

        $result = $cache->delete_for_post(1);

        expect($result)->toBe(1);
        // Post 2 transient should still exist.
        expect($cache->get('crispy_md_2_ddeeff'))->toBe('content B');
    });
});

describe('TransientCache::clear_all', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns 0 when there are no cached transients', function () {
        global $wpdb;
        $wpdb = new MockWpdb([]);

        $cache  = new TransientCache();
        $result = $cache->clear_all();

        expect($result)->toBe(0);
    });

    it('deletes all cached transients and returns count', function () {
        global $wpdb;

        $cache = new TransientCache();
        $cache->set('crispy_md_1_aaa', 'html 1');
        $cache->set('crispy_md_2_bbb', 'html 2');
        $cache->set('crispy_md_3_ccc', 'html 3');

        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_1_aaa'],
            ['option_name' => '_transient_crispy_md_2_bbb'],
            ['option_name' => '_transient_crispy_md_3_ccc'],
        ]);

        $result = $cache->clear_all();

        expect($result)->toBe(3);
    });

    it('clears all transients from the store', function () {
        global $wpdb;

        $cache = new TransientCache();
        $cache->set('crispy_md_5_xxx', 'html 5');

        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_5_xxx'],
        ]);

        $cache->clear_all();

        expect($cache->get('crispy_md_5_xxx'))->toBeFalse();
    });
});

describe('TransientCache::get_stats', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns an array with count and total_size keys', function () {
        global $wpdb;
        $wpdb = new MockWpdb([]);

        $cache = new TransientCache();
        $stats = $cache->get_stats();

        expect($stats)->toBeArray();
        expect(array_key_exists('count', $stats))->toBeTrue();
        expect(array_key_exists('total_size', $stats))->toBeTrue();
    });

    it('returns zero count and size when no transients exist', function () {
        global $wpdb;
        $wpdb = new MockWpdb([]);

        $cache = new TransientCache();
        $stats = $cache->get_stats();

        expect($stats['count'])->toBe(0);
        expect($stats['total_size'])->toBe(0);
    });

    it('returns correct count when transients exist', function () {
        global $wpdb;

        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_1_aaa', 'size' => 100],
            ['option_name' => '_transient_crispy_md_2_bbb', 'size' => 200],
        ]);

        $cache = new TransientCache();
        $stats = $cache->get_stats();

        expect($stats['count'])->toBe(2);
    });

    it('sums sizes correctly in total_size', function () {
        global $wpdb;

        $wpdb = new MockWpdb([
            ['option_name' => '_transient_crispy_md_1_aaa', 'size' => 150],
            ['option_name' => '_transient_crispy_md_2_bbb', 'size' => 350],
        ]);

        $cache = new TransientCache();
        $stats = $cache->get_stats();

        expect($stats['total_size'])->toBe(500);
    });
});

describe('TransientCache::get edge cases', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('casts non-false cached value to string', function () {
        // set_transient stores an integer — get must return string.
        Functions\when('get_transient')->justReturn('42');

        $cache  = new TransientCache();
        $result = $cache->get('some_key');

        expect($result)->toBeString();
        expect($result)->toBe('42');
    });

    it('returns false when get_transient returns false', function () {
        Functions\when('get_transient')->justReturn(false);

        $cache  = new TransientCache();
        $result = $cache->get('missing_key');

        expect($result)->toBeFalse();
    });
});

describe('TransientCache::set with explicit expiration', function () {
    beforeEach(function () {
        $this->setupTransients();
    });

    it('bypasses filter when explicit expiration is provided', function () {
        $filterCalled = false;

        Functions\when('apply_filters')->alias(function () use (&$filterCalled): mixed {
            $filterCalled = true;
            return 86400;
        });

        $cache = new TransientCache();
        $cache->set('key', 'value', 3600);

        // Filter should not be invoked when expiration is given explicitly.
        expect($filterCalled)->toBeFalse();
    });

    it('stores the value successfully with explicit expiration', function () {
        Functions\when('apply_filters')->returnArg(2);

        $cache = new TransientCache();
        $cache->set('explicit_key', 'explicit_value', 7200);

        expect($cache->get('explicit_key'))->toBe('explicit_value');
    });
});
