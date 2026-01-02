<?php
/**
 * CrispyTheme functions and definitions.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme version constant.
 */
define( 'CRISPY_THEME_VERSION', '1.0.1' );

/**
 * Theme directory path.
 */
define( 'CRISPY_THEME_DIR', get_template_directory() );

/**
 * Theme directory URI.
 */
define( 'CRISPY_THEME_URI', get_template_directory_uri() );

/**
 * Minimum PHP version required.
 */
define( 'CRISPY_THEME_MIN_PHP', '8.1' );

/**
 * Minimum WordPress version required.
 */
define( 'CRISPY_THEME_MIN_WP', '6.6' );

/**
 * Load Composer autoloader.
 *
 * @return void
 */
function crispy_theme_autoload(): void {
	$autoloader = CRISPY_THEME_DIR . '/vendor/autoload.php';

	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	} else {
		// Display admin notice if autoloader is missing.
		add_action(
			'admin_notices',
			static function (): void {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__(
					'CrispyTheme: Composer dependencies are not installed. Please run "composer install" in the theme directory.',
					'crispy-theme'
				);
				echo '</p></div>';
			}
		);
	}
}

/**
 * Check system requirements.
 *
 * @return bool True if requirements are met, false otherwise.
 */
function crispy_theme_check_requirements(): bool {
	$errors = [];

	// Check PHP version.
	if ( version_compare( PHP_VERSION, CRISPY_THEME_MIN_PHP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			__( 'CrispyTheme requires PHP %1$s or higher. You are running PHP %2$s.', 'crispy-theme' ),
			CRISPY_THEME_MIN_PHP,
			PHP_VERSION
		);
	}

	// Check WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, CRISPY_THEME_MIN_WP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required WordPress version, 2: Current WordPress version */
			__( 'CrispyTheme requires WordPress %1$s or higher. You are running WordPress %2$s.', 'crispy-theme' ),
			CRISPY_THEME_MIN_WP,
			$wp_version
		);
	}

	// Display errors if any.
	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			static function () use ( $errors ): void {
				foreach ( $errors as $error ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
				}
			}
		);
		return false;
	}

	return true;
}

/**
 * Initialize the theme.
 *
 * @return void
 */
function crispy_theme_init(): void {
	// Load autoloader.
	crispy_theme_autoload();

	// Check requirements.
	if ( ! crispy_theme_check_requirements() ) {
		return;
	}

	// Only proceed if autoloader exists.
	if ( ! class_exists( Theme\Setup::class ) ) {
		return;
	}

	// Initialize theme components.
	$setup = new Theme\Setup();
	$setup->init();

	// Initialize assets.
	$assets = new Theme\Assets();
	$assets->init();

	// Initialize Markdown renderer.
	$markdown_renderer = new Content\MarkdownRenderer();
	$markdown_renderer->init();

	// Initialize excerpt generator.
	$excerpt_generator = new Content\ExcerptGenerator();
	$excerpt_generator->init();

	// Initialize RSS filter.
	$rss_filter = new Content\RSSFilter();
	$rss_filter->init();

	// Initialize dark mode.
	$dark_mode = new DarkMode\Toggle();
	$dark_mode->init();

	// Initialize pattern manager.
	$pattern_manager = new Patterns\PatternManager();
	$pattern_manager->init();

	// Initialize newsletter signup block.
	$newsletter_block = new Newsletter\SignupBlock();
	$newsletter_block->init();

	// Initialize collapsible code block.
	$collapsible_block = new Code\CollapsibleBlock();
	$collapsible_block->init();

	// Admin-only components.
	if ( is_admin() ) {
		// Disable block editor.
		$editor = new Admin\Editor();
		$editor->init();

		// Register markdown metabox.
		$metabox = new Admin\MetaBox();
		$metabox->init();

		// Register preview handler.
		$preview = new Admin\Preview();
		$preview->init();

		// Register options page.
		$options = new Admin\OptionsPage();
		$options->init();
	}

	// WP-CLI commands.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\WP_CLI::add_command( 'crispy', CLI\ImportCommand::class );
	}
}

// Hook into WordPress.
add_action( 'after_setup_theme', __NAMESPACE__ . '\crispy_theme_init' );

/**
 * Display soft recommendation for CrispySEO plugin.
 *
 * Shows a dismissible admin notice recommending the standalone
 * CrispySEO plugin if it's not already active.
 *
 * @return void
 */
function crispy_theme_seo_recommendation(): void {
	// Skip if CrispySEO plugin is active.
	if ( defined( 'CRISPY_SEO_VERSION' ) ) {
		return;
	}

	// Skip if user has dismissed the notice.
	$dismissed = get_user_meta( get_current_user_id(), 'crispy_seo_notice_dismissed', true );
	if ( $dismissed ) {
		return;
	}

	// Only show on relevant admin pages.
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, [ 'dashboard', 'themes', 'plugins' ], true ) ) {
		return;
	}

	echo '<div class="notice notice-info is-dismissible" id="crispy-seo-recommendation">';
	echo '<p><strong>' . esc_html__( 'CrispyTheme Recommendation:', 'crispy-theme' ) . '</strong> ';
	echo esc_html__( 'For SEO features (meta tags, schema, sitemaps, redirects, image optimization), install the CrispySEO plugin.', 'crispy-theme' );
	echo '</p></div>';

	// Add inline script to handle dismissal.
	?>
	<script>
	jQuery(document).ready(function($) {
		$('#crispy-seo-recommendation').on('click', '.notice-dismiss', function() {
			$.post(ajaxurl, {
				action: 'crispy_dismiss_seo_notice',
				nonce: '<?php echo esc_js( wp_create_nonce( 'crispy_dismiss_seo_notice' ) ); ?>'
			});
		});
	});
	</script>
	<?php
}
add_action( 'admin_notices', __NAMESPACE__ . '\crispy_theme_seo_recommendation' );

/**
 * Handle AJAX request to dismiss SEO recommendation notice.
 *
 * @return void
 */
function crispy_theme_dismiss_seo_notice(): void {
	check_ajax_referer( 'crispy_dismiss_seo_notice', 'nonce' );
	update_user_meta( get_current_user_id(), 'crispy_seo_notice_dismissed', '1' );
	wp_die();
}
add_action( 'wp_ajax_crispy_dismiss_seo_notice', __NAMESPACE__ . '\crispy_theme_dismiss_seo_notice' );
