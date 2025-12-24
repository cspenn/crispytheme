<?php
/**
 * Theme Setup class.
 *
 * Handles theme initialization, theme supports, and core WordPress hooks.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Theme;

/**
 * Theme Setup class.
 */
class Setup {

	/**
	 * Initialize the theme setup.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
		add_action( 'init', [ $this, 'register_block_patterns' ] );
		add_action( 'init', [ $this, 'register_block_styles' ] );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}

	/**
	 * Set up theme defaults and register support for various WordPress features.
	 *
	 * @return void
	 */
	public function setup_theme(): void {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		// Let WordPress manage the document title.
		add_theme_support( 'title-tag' );

		// Enable support for Post Thumbnails on posts and pages.
		add_theme_support( 'post-thumbnails' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Add support for Block Styles.
		add_theme_support( 'wp-block-styles' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Add support for HTML5 markup.
		add_theme_support(
			'html5',
			[
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			]
		);

		// Add support for custom logo.
		add_theme_support(
			'custom-logo',
			[
				'height'      => 100,
				'width'       => 400,
				'flex-height' => true,
				'flex-width'  => true,
			]
		);

		// Add support for custom background.
		add_theme_support(
			'custom-background',
			[
				'default-color' => 'ffffff',
			]
		);

		// Register navigation menus.
		register_nav_menus(
			[
				'primary' => __( 'Primary Menu', 'crispy-theme' ),
				'footer'  => __( 'Footer Menu', 'crispy-theme' ),
				'social'  => __( 'Social Links Menu', 'crispy-theme' ),
			]
		);

		// Load text domain for translations.
		load_theme_textdomain(
			'crispy-theme',
			CRISPY_THEME_DIR . '/languages'
		);

		// Remove core block patterns (we'll add our own if needed).
		remove_theme_support( 'core-block-patterns' );

		// Add support for wide and full alignments.
		add_theme_support( 'align-wide' );

		// Set content width.
		$GLOBALS['content_width'] = 720;
	}

	/**
	 * Register custom block patterns.
	 *
	 * @return void
	 */
	public function register_block_patterns(): void {
		// Register pattern category for CrispyTheme.
		register_block_pattern_category(
			'crispytheme',
			[
				'label' => __( 'CrispyTheme', 'crispy-theme' ),
			]
		);

		// Patterns will be added here as needed.
	}

	/**
	 * Register custom block styles.
	 *
	 * @return void
	 */
	public function register_block_styles(): void {
		// Register outline button style.
		register_block_style(
			'core/button',
			[
				'name'  => 'outline',
				'label' => __( 'Outline', 'crispy-theme' ),
			]
		);

		// Register plain list style.
		register_block_style(
			'core/list',
			[
				'name'  => 'plain',
				'label' => __( 'Plain', 'crispy-theme' ),
			]
		);

		// Register shadow box style for groups.
		register_block_style(
			'core/group',
			[
				'name'  => 'shadow-box',
				'label' => __( 'Shadow Box', 'crispy-theme' ),
			]
		);
	}
}
