<?php
/**
 * Markdown Dropdown Analytics Tests
 *
 * Tests the analytics tracking functionality for the markdown dropdown.
 *
 * @package CrispyTheme\Tests\Unit
 */

declare(strict_types=1);

use CrispyTheme\Content\MarkdownDropdown;
use Brain\Monkey\Functions;

describe('MarkdownDropdown Analytics', function () {

	beforeEach(function () {
		Brain\Monkey\setUp();

		// Define WordPress constant if not defined.
		if ( ! defined( 'ARRAY_A' ) ) {
			define( 'ARRAY_A', 'ARRAY_A' );
		}
	});

	afterEach(function () {
		Brain\Monkey\tearDown();
	});

	it('returns correct nonce action string', function () {
		$action = MarkdownDropdown::get_nonce_action();
		expect($action)->toBe('crispy_markdown_dropdown');
	});

	it('returns correct tracking nonce action string', function () {
		$action = MarkdownDropdown::get_tracking_nonce_action();
		expect($action)->toBe('crispy_markdown_analytics');
	});

	it('has correct meta key constants for copy count', function () {
		expect(MarkdownDropdown::META_COPY_COUNT)->toBe('_crispy_markdown_copy_count');
	});

	it('has correct meta key constants for view count', function () {
		expect(MarkdownDropdown::META_VIEW_COUNT)->toBe('_crispy_markdown_view_count');
	});

	it('has correct query var constant', function () {
		expect(MarkdownDropdown::QUERY_VAR)->toBe('crispy_raw');
	});

	it('returns default analytics stats when option is empty', function () {
		Functions\when('get_option')->justReturn([]);

		$stats = MarkdownDropdown::get_analytics_stats();

		expect($stats)->toBeArray();
		expect($stats)->toHaveKey('total_copies');
		expect($stats)->toHaveKey('total_views');
		expect($stats)->toHaveKey('last_updated');
		expect($stats['total_copies'])->toBe(0);
		expect($stats['total_views'])->toBe(0);
		expect($stats['last_updated'])->toBeNull();
	});

	it('returns stored analytics stats', function () {
		$stored_stats = [
			'total_copies'  => 150,
			'total_views'   => 300,
			'last_updated'  => '2026-01-14 12:00:00',
		];

		Functions\when('get_option')->justReturn($stored_stats);

		$stats = MarkdownDropdown::get_analytics_stats();

		expect($stats['total_copies'])->toBe(150);
		expect($stats['total_views'])->toBe(300);
		expect($stats['last_updated'])->toBe('2026-01-14 12:00:00');
	});

	it('merges defaults with partial stored stats', function () {
		$partial_stats = [
			'total_copies' => 50,
		];

		Functions\when('get_option')->justReturn($partial_stats);

		$stats = MarkdownDropdown::get_analytics_stats();

		expect($stats['total_copies'])->toBe(50);
		expect($stats['total_views'])->toBe(0);
		expect($stats['last_updated'])->toBeNull();
	});

});
