<?php
/**
 * Transient Cache class.
 *
 * Provides a wrapper around WordPress Transients API for caching parsed markdown.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Cache;

/**
 * Transient Cache class.
 */
class TransientCache {

	/**
	 * Cache key prefix.
	 */
	private const PREFIX = 'crispy_md_';

	/**
	 * Default cache expiration in seconds (1 day = 86400).
	 */
	private const DEFAULT_EXPIRATION = 86400;

	/**
	 * Generate a cache key for the given post ID and content.
	 *
	 * Uses xxh3 hash (PHP 8.1+) for fast content hashing.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $content The markdown content.
	 * @return string The cache key.
	 */
	public function generate_key( int $post_id, string $content ): string {
		// Use xxh3 for faster hashing (available in PHP 8.1+).
		$content_hash = hash( 'xxh3', $content );

		return self::PREFIX . $post_id . '_' . $content_hash;
	}

	/**
	 * Get cached content.
	 *
	 * @param string $key The cache key.
	 * @return string|false The cached content, or false if not found.
	 */
	public function get( string $key ): string|false {
		$cached = get_transient( $key );

		if ( false === $cached ) {
			return false;
		}

		return (string) $cached;
	}

	/**
	 * Set cached content.
	 *
	 * @param string   $key        The cache key.
	 * @param string   $content    The content to cache.
	 * @param int|null $expiration Optional. Cache expiration in seconds.
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, string $content, ?int $expiration = null ): bool {
		if ( null === $expiration ) {
			/**
			 * Filter the cache expiration time.
			 *
			 * @param int $expiration Expiration time in seconds.
			 */
			$expiration = apply_filters( 'crispytheme_cache_expiration', self::DEFAULT_EXPIRATION );
		}

		return set_transient( $key, $content, $expiration );
	}

	/**
	 * Delete cached content.
	 *
	 * @param string $key The cache key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		return delete_transient( $key );
	}

	/**
	 * Delete all cached content for a specific post.
	 *
	 * This uses a pattern-based deletion approach.
	 *
	 * @param int $post_id The post ID.
	 * @return int Number of transients deleted.
	 */
	public function delete_for_post( int $post_id ): int {
		global $wpdb;

		$pattern = self::PREFIX . $post_id . '_%';

		// Get all matching transient names.
		$transient_prefix = '_transient_';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for pattern-based transient deletion.
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$transient_prefix . $pattern
			)
		);

		$count = 0;
		foreach ( $results as $option_name ) {
			// Extract the transient name from the option name.
			$transient_name = str_replace( $transient_prefix, '', $option_name );
			if ( delete_transient( $transient_name ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Clear all markdown cache.
	 *
	 * @return int Number of transients deleted.
	 */
	public function clear_all(): int {
		global $wpdb;

		$pattern = self::PREFIX . '%';

		// Get all matching transient names.
		$transient_prefix = '_transient_';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for pattern-based transient deletion.
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$transient_prefix . $pattern
			)
		);

		$count = 0;
		foreach ( $results as $option_name ) {
			$transient_name = str_replace( $transient_prefix, '', $option_name );
			if ( delete_transient( $transient_name ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array{count: int, total_size: int} Cache statistics.
	 */
	public function get_stats(): array {
		global $wpdb;

		$pattern          = self::PREFIX . '%';
		$transient_prefix = '_transient_';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for cache statistics.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, LENGTH(option_value) as size
                FROM {$wpdb->options}
                WHERE option_name LIKE %s",
				$transient_prefix . $pattern
			)
		);

		$total_size = 0;
		foreach ( $results as $row ) {
			$total_size += (int) $row->size;
		}

		return [
			'count'      => count( $results ),
			'total_size' => $total_size,
		];
	}
}
