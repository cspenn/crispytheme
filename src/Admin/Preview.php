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

use CrispyTheme\Content\MarkdownRenderer;

/**
 * Admin Preview class.
 */
class Preview {

	/**
	 * The AJAX action name.
	 */
	private const AJAX_ACTION = 'crispytheme_preview_markdown';

	/**
	 * Initialize the preview handler.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_preview_request' ] );
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
}
