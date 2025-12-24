<?php
/**
 * Pattern Manager class.
 *
 * Handles registration of block patterns and pattern categories.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Patterns;

/**
 * Pattern Manager class.
 */
class PatternManager {

	/**
	 * Initialize the pattern manager.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_pattern_categories' ] );
		add_action( 'init', [ $this, 'register_patterns_from_directory' ], 11 );
	}

	/**
	 * Register custom pattern categories.
	 *
	 * @return void
	 */
	public function register_pattern_categories(): void {
		$categories = [
			'crispytheme-trust'      => __( 'Trust Signals', 'crispy-theme' ),
			'crispytheme-newsletter' => __( 'Newsletter', 'crispy-theme' ),
			'crispytheme-speaking'   => __( 'Speaking', 'crispy-theme' ),
			'crispytheme-code'       => __( 'Code Showcase', 'crispy-theme' ),
		];

		foreach ( $categories as $slug => $label ) {
			register_block_pattern_category(
				$slug,
				[
					'label' => $label,
				]
			);
		}
	}

	/**
	 * Register patterns from the patterns directory.
	 *
	 * Each pattern file should be a PHP file that registers its own pattern
	 * using register_block_pattern(). The file should return early if the
	 * function doesn't exist.
	 *
	 * @return void
	 */
	public function register_patterns_from_directory(): void {
		$patterns_dir = CRISPY_THEME_DIR . '/patterns';

		if ( ! is_dir( $patterns_dir ) ) {
			return;
		}

		$pattern_files = glob( $patterns_dir . '/*.php' );

		// Ensure glob() returned a valid array.
		if ( false === $pattern_files || empty( $pattern_files ) ) {
			return;
		}

		foreach ( $pattern_files as $pattern_file ) {
			// Validate file before inclusion.
			if ( is_readable( $pattern_file ) && is_file( $pattern_file ) ) {
				require_once $pattern_file;
			}
		}
	}
}
