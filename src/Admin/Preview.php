<?php
/**
 * Admin Preview class.
 *
 * Handles AJAX preview functionality for the markdown editor.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Admin;

use CrispyTheme\Content\HtmlToMarkdownConverter;
use CrispyTheme\Content\MarkdownRenderer;

/**
 * Admin Preview class.
 */
class Preview {

	/**
	 * The AJAX action name for preview.
	 */
	private const AJAX_ACTION = 'crispytheme_preview_markdown';

	/**
	 * The AJAX action name for re-convert.
	 */
	private const AJAX_RECONVERT_ACTION = 'crispytheme_reconvert_markdown';

	/**
	 * Initialize the preview handler.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_preview_request' ] );
		add_action( 'wp_ajax_' . self::AJAX_RECONVERT_ACTION, [ $this, 'handle_reconvert_request' ] );

		// Debug: Add a simple test action to verify AJAX is working.
		add_action( 'wp_ajax_crispytheme_debug_test', [ $this, 'handle_debug_test' ] );
	}

	/**
	 * Debug test handler to verify AJAX is working.
	 *
	 * @return void
	 */
	public function handle_debug_test(): void {
		wp_send_json_success(
			[
				'message'   => 'CrispyTheme AJAX is working!',
				'timestamp' => gmdate( 'Y-m-d H:i:s' ),
				'php'       => PHP_VERSION,
			]
		);
	}

	/**
	 * Handle the AJAX preview request.
	 *
	 * @return void
	 */
	public function handle_preview_request(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( 'crispy_theme_preview', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed.', 'crispy-theme' ),
				],
				403
			);
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to preview content.', 'crispy-theme' ),
				],
				403
			);
		}

		// Get the markdown content (markdown may contain valid HTML, sanitized during render).
		$markdown = isset( $_POST['markdown'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Intentionally allowing raw markdown.
			? wp_unslash( $_POST['markdown'] )
			: '';

		// Return empty preview for empty content.
		if ( empty( $markdown ) ) {
			wp_send_json_success(
				[
					'html'       => '<p class="crispy-markdown-editor__placeholder">' .
									esc_html__( 'Nothing to preview. Start writing to see your content.', 'crispy-theme' ) .
									'</p>',
					'word_count' => 0,
					'char_count' => 0,
				]
			);
		}

		// Render the markdown.
		$renderer = new MarkdownRenderer();
		$html     = $renderer->parse_without_cache( $markdown );

		// Calculate stats.
		$plain_text = wp_strip_all_tags( $html );
		$word_count = str_word_count( $plain_text );
		$char_count = mb_strlen( $markdown );

		wp_send_json_success(
			[
				'html'       => $html,
				'word_count' => $word_count,
				'char_count' => $char_count,
			]
		);
	}

	/**
	 * Get the AJAX action name.
	 *
	 * @return string The AJAX action.
	 */
	public static function get_ajax_action(): string {
		return self::AJAX_ACTION;
	}

	/**
	 * Handle the AJAX re-convert request.
	 *
	 * Re-converts HTML content to Markdown when user clicks the re-convert button.
	 *
	 * @return void
	 */
	public function handle_reconvert_request(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( 'crispy_theme_preview', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed.', 'crispy-theme' ),
				],
				403
			);
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to edit content.', 'crispy-theme' ),
				],
				403
			);
		}

		// Get the post ID.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( $post_id < 1 ) {
			wp_send_json_error(
				[
					'message' => __( 'Invalid post ID.', 'crispy-theme' ),
				],
				400
			);
		}

		// Get the post.
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			wp_send_json_error(
				[
					'message' => __( 'Post not found.', 'crispy-theme' ),
				],
				404
			);
		}

		// Check if post has HTML content to convert.
		if ( empty( trim( $post->post_content ) ) ) {
			wp_send_json_error(
				[
					'message' => __( 'No HTML content to convert.', 'crispy-theme' ),
				],
				400
			);
		}

		// Convert HTML to Markdown.
		$converter = new HtmlToMarkdownConverter();
		$markdown  = $converter->convert( $post->post_content );

		if ( empty( $markdown ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Conversion failed. Please try again.', 'crispy-theme' ),
				],
				500
			);
		}

		wp_send_json_success(
			[
				'markdown' => $markdown,
				'message'  => __( 'Content re-converted successfully. Review and save when ready.', 'crispy-theme' ),
			]
		);
	}
}
