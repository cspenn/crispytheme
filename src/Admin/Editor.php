<?php
/**
 * Admin Editor class.
 *
 * Handles disabling the Block Editor (Gutenberg) for all post types.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Admin;

/**
 * Admin Editor class.
 */
class Editor {

	/**
	 * Initialize the editor modifications.
	 *
	 * @return void
	 */
	public function init(): void {
		// Disable block editor for all post types.
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );

		// Disable block-based widgets.
		add_filter( 'use_widgets_block_editor', '__return_false' );

		// Remove block library CSS from frontend (optional, controlled by filter).
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_remove_block_styles' ], 100 );

		// Add admin notice explaining the markdown editor.
		add_action( 'admin_notices', [ $this, 'show_markdown_notice' ] );

		// Hide the default content editor.
		add_action( 'admin_head', [ $this, 'hide_default_editor' ] );
	}

	/**
	 * Conditionally remove block library styles from frontend.
	 *
	 * @return void
	 */
	public function maybe_remove_block_styles(): void {
		/**
		 * Filter whether to remove block library CSS.
		 *
		 * Default is false (keep block CSS) because the theme uses block templates
		 * that require WordPress's block library CSS for proper layout rendering.
		 * Set to true only if you're providing all block styling manually.
		 *
		 * @param bool $remove Whether to remove block library CSS. Default false.
		 */
		$remove = apply_filters( 'crispytheme_remove_block_library_css', false );

		if ( ! $remove ) {
			return;
		}

		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-blocks-style' ); // WooCommerce blocks if present.
	}

	/**
	 * Show admin notice about the markdown editor on post edit screens.
	 *
	 * @return void
	 */
	public function show_markdown_notice(): void {
		$screen = get_current_screen();

		// Only show on post edit screens.
		if ( ! $screen || ! in_array( $screen->base, [ 'post', 'post-new' ], true ) ) {
			return;
		}

		// Only show once per session.
		$dismissed = get_user_meta( get_current_user_id(), 'crispytheme_markdown_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible" data-crispy-notice="markdown-editor">
			<p>
				<strong><?php esc_html_e( 'CrispyTheme Markdown Editor', 'crispy-theme' ); ?></strong>
			</p>
			<p>
				<?php
				esc_html_e(
					'This theme uses a markdown-first approach. Enter your content in the "Markdown Content" metabox below. The standard WordPress editor has been disabled.',
					'crispy-theme'
				);
				?>
			</p>
			<p>
				<a href="https://www.markdownguide.org/cheat-sheet/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Markdown Cheat Sheet', 'crispy-theme' ); ?>
				</a>
			</p>
		</div>
		<script>
			jQuery(document).on('click', '[data-crispy-notice="markdown-editor"] .notice-dismiss', function() {
				jQuery.post(ajaxurl, {
					action: 'crispytheme_dismiss_notice',
					notice: 'markdown-editor',
					nonce: '<?php echo esc_js( wp_create_nonce( 'crispy_dismiss_notice' ) ); ?>'
				});
			});
		</script>
		<?php
	}

	/**
	 * Hide the default content editor.
	 *
	 * @return void
	 */
	public function hide_default_editor(): void {
		$screen = get_current_screen();

		// Only on post edit screens.
		if ( ! $screen || ! in_array( $screen->base, [ 'post', 'post-new' ], true ) ) {
			return;
		}

		?>
		<style>
			/* Hide the classic editor content area */
			#postdivrich,
			#wp-content-wrap,
			#post-body-content > #titlediv + div:not(.postbox) {
				display: none !important;
			}

			/* Ensure the markdown metabox is prominent */
			#markdown_editor {
				margin-top: 20px;
			}

			#markdown_editor .inside {
				padding: 0;
				margin: 0;
			}
		</style>
		<?php
	}

	/**
	 * Handle notice dismissal via AJAX.
	 *
	 * @return void
	 */
	public static function handle_notice_dismissal(): void {
		add_action( 'wp_ajax_crispytheme_dismiss_notice', [ __CLASS__, 'dismiss_notice' ] );
	}

	/**
	 * Dismiss a notice for the current user.
	 *
	 * @return void
	 */
	public static function dismiss_notice(): void {
		check_ajax_referer( 'crispy_dismiss_notice', 'nonce' );

		$notice = sanitize_key( $_POST['notice'] ?? '' );

		if ( 'markdown-editor' === $notice ) {
			update_user_meta( get_current_user_id(), 'crispytheme_markdown_notice_dismissed', true );
		}

		wp_send_json_success();
	}
}
