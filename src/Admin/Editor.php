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

		// Add Site Editor notice guiding users to classic edit screens.
		add_action( 'admin_notices', [ $this, 'show_site_editor_notice' ] );

		// Hide the default content editor.
		add_action( 'admin_head', [ $this, 'hide_default_editor' ] );

		// Add "Edit with Markdown" row actions to Posts/Pages list tables.
		add_filter( 'page_row_actions', [ $this, 'add_edit_markdown_link' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'add_edit_markdown_link' ], 10, 2 );
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

	/**
	 * Replace "Edit" link text with "Edit Markdown" to clarify editing mode.
	 *
	 * Since the block editor is disabled, the standard "Edit" link already
	 * takes users to the classic editor with the markdown metabox.
	 * This method updates the link text to make that clear.
	 *
	 * @param array<string,string> $actions The existing row actions.
	 * @param \WP_Post             $post    The post object.
	 * @return array<string,string> Modified row actions.
	 */
	public function add_edit_markdown_link( array $actions, \WP_Post $post ): array {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		// Replace the standard "Edit" link text to clarify it's markdown editing.
		if ( isset( $actions['edit'] ) ) {
			$post_title = ! empty( $post->post_title )
				? $post->post_title
				: __( '(no title)', 'crispy-theme' );

			$edit_url = admin_url( "post.php?post={$post->ID}&action=edit" );

			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url( $edit_url ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221; with Markdown', 'crispy-theme' ), $post_title ) ),
				esc_html__( 'Edit Markdown', 'crispy-theme' )
			);
		}

		return $actions;
	}

	/**
	 * Show notice on Site Editor screen directing users to classic edit.
	 *
	 * The Site Editor (FSE) doesn't support metaboxes, so users need to
	 * use the classic post/page edit screens for the markdown editor.
	 *
	 * @return void
	 */
	public function show_site_editor_notice(): void {
		$screen = get_current_screen();

		// Only show on Site Editor screen.
		if ( ! $screen || 'site-editor' !== $screen->base ) {
			return;
		}

		$pages_url = esc_url( admin_url( 'edit.php?post_type=page' ) );
		$posts_url = esc_url( admin_url( 'edit.php' ) );
		$pages_text = esc_html__( 'Pages', 'crispy-theme' );
		$posts_text = esc_html__( 'Posts', 'crispy-theme' );

		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'CrispyTheme Markdown Editor', 'crispy-theme' ); ?></strong><br>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %1$s: Pages menu link, %2$s: Posts menu link */
						__( 'To edit page/post content with the Markdown editor, use %1$s or %2$s instead.', 'crispy-theme' ),
						'<a href="' . $pages_url . '">' . $pages_text . '</a>',
						'<a href="' . $posts_url . '">' . $posts_text . '</a>'
					),
					[
						'a' => [
							'href' => [],
						],
					]
				);
				?>
			</p>
		</div>
		<?php
	}
}
