<?php
/**
 * Admin MetaBox class.
 *
 * Registers and renders the Markdown content metabox with toggle preview.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Admin;

use CrispyTheme\Content\HtmlToMarkdownConverter;
use CrispyTheme\Content\MarkdownRenderer;

/**
 * Admin MetaBox class.
 */
class MetaBox {

	/**
	 * The metabox ID.
	 */
	private const METABOX_ID = 'markdown_editor';

	/**
	 * The HTML to Markdown converter.
	 *
	 * @var HtmlToMarkdownConverter
	 */
	private HtmlToMarkdownConverter $converter;

	/**
	 * Constructor.
	 *
	 * Initializes the HTML to Markdown converter.
	 */
	public function __construct() {
		$this->converter = new HtmlToMarkdownConverter();
	}

	/**
	 * The nonce action.
	 */
	private const NONCE_ACTION = 'crispytheme_save_markdown';

	/**
	 * The nonce field name.
	 */
	private const NONCE_FIELD = 'crispytheme_markdown_nonce';

	/**
	 * Initialize the metabox.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_markdown_content' ], 10, 2 );
	}

	/**
	 * Register the markdown metabox.
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		// Get all public post types that support editor.
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'names'
		);

		foreach ( $post_types as $post_type ) {
			// Only add to post types that support editor.
			if ( ! post_type_supports( $post_type, 'editor' ) ) {
				continue;
			}

			add_meta_box(
				self::METABOX_ID,
				__( 'Markdown Content', 'crispy-theme' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the markdown metabox.
	 *
	 * @param \WP_Post $post The post object.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$markdown      = get_post_meta( $post->ID, MarkdownRenderer::META_KEY, true );
		$markdown      = is_string( $markdown ) ? $markdown : '';
		$was_converted = false;

		// If no markdown but has HTML content, attempt conversion.
		if ( empty( $markdown ) && ! empty( trim( $post->post_content ) ) ) {
			$markdown      = $this->converter->convert( $post->post_content );
			$was_converted = true;
		}

		// Add nonce field.
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		// Show notice if content was auto-converted.
		if ( $was_converted && ! empty( $markdown ) ) {
			?>
			<div class="notice notice-warning inline" style="margin: 0 0 12px 0;">
				<p>
					<strong><?php esc_html_e( 'Content Auto-Converted', 'crispy-theme' ); ?></strong><br>
					<?php esc_html_e( 'Your existing HTML content has been converted to Markdown. Please review carefully before saving.', 'crispy-theme' ); ?>
				</p>
			</div>
			<?php
		}

		// Add re-convert button for posts with existing markdown (not freshly converted).
		if ( ! $was_converted && ! empty( $markdown ) && ! empty( trim( $post->post_content ) ) ) {
			?>
			<div class="crispy-markdown-editor__reconvert" style="margin: 0 0 12px 0; padding: 8px 12px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
				<button type="button"
						class="button button-secondary"
						id="crispy-reconvert-btn">
					<?php esc_html_e( 'Re-convert from HTML', 'crispy-theme' ); ?>
				</button>
				<span class="description" style="margin-left: 8px;">
					<?php esc_html_e( 'Discard current markdown and re-convert from original HTML', 'crispy-theme' ); ?>
				</span>
			</div>
			<?php
		}
		?>
		<div class="crispy-markdown-editor" id="crispy-markdown-editor">
			<div class="crispy-markdown-editor__toolbar">
				<div class="crispy-markdown-editor__tabs">
					<button type="button"
							class="crispy-markdown-editor__tab crispy-markdown-editor__tab--active"
							data-tab="edit"
							aria-selected="true">
						<?php esc_html_e( 'Edit', 'crispy-theme' ); ?>
					</button>
					<button type="button"
							class="crispy-markdown-editor__tab"
							data-tab="preview"
							aria-selected="false">
						<?php esc_html_e( 'Preview', 'crispy-theme' ); ?>
					</button>
				</div>
				<div class="crispy-markdown-editor__actions">
					<a href="https://www.markdownguide.org/cheat-sheet/"
						target="_blank"
						rel="noopener noreferrer"
						class="crispy-markdown-editor__help">
						<?php esc_html_e( 'Markdown Help', 'crispy-theme' ); ?>
					</a>
				</div>
			</div>

			<div class="crispy-markdown-editor__content">
				<div class="crispy-markdown-editor__panel crispy-markdown-editor__panel--edit"
					data-panel="edit">
					<textarea
						name="crispy_markdown_content"
						id="crispy_markdown_content"
						class="crispy-markdown-editor__textarea"
						rows="20"
						placeholder="<?php esc_attr_e( 'Write your content in Markdown...', 'crispy-theme' ); ?>"
					><?php echo esc_textarea( $markdown ); ?></textarea>
				</div>

				<div class="crispy-markdown-editor__panel crispy-markdown-editor__panel--preview"
					data-panel="preview"
					style="display: none;">
					<div class="crispy-markdown-editor__preview markdown-body" id="crispy-markdown-preview">
						<?php
						if ( ! empty( $markdown ) ) {
							$renderer = new MarkdownRenderer();
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown HTML is trusted.
							echo $renderer->parse_without_cache( $markdown );
						} else {
							echo '<p class="crispy-markdown-editor__placeholder">';
							esc_html_e( 'Nothing to preview. Start writing to see your content.', 'crispy-theme' );
							echo '</p>';
						}
						?>
					</div>
				</div>
			</div>

			<div class="crispy-markdown-editor__footer">
				<span class="crispy-markdown-editor__stats">
					<span id="crispy-char-count">0</span> <?php esc_html_e( 'characters', 'crispy-theme' ); ?>,
					<span id="crispy-word-count">0</span> <?php esc_html_e( 'words', 'crispy-theme' ); ?>
				</span>
			</div>
		</div>

		<style>
			.crispy-markdown-editor {
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				background: #fff;
			}

			.crispy-markdown-editor__toolbar {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 8px 12px;
				border-bottom: 1px solid #c3c4c7;
				background: #f6f7f7;
			}

			.crispy-markdown-editor__tabs {
				display: flex;
				gap: 4px;
			}

			.crispy-markdown-editor__tab {
				padding: 6px 16px;
				border: 1px solid transparent;
				border-radius: 4px;
				background: transparent;
				cursor: pointer;
				font-size: 13px;
				color: #50575e;
			}

			.crispy-markdown-editor__tab:hover {
				background: #fff;
			}

			.crispy-markdown-editor__tab--active {
				background: #fff;
				border-color: #c3c4c7;
				color: #1d2327;
				font-weight: 500;
			}

			.crispy-markdown-editor__help {
				font-size: 12px;
				color: #2271b1;
				text-decoration: none;
			}

			.crispy-markdown-editor__help:hover {
				text-decoration: underline;
			}

			.crispy-markdown-editor__content {
				min-height: 400px;
			}

			.crispy-markdown-editor__textarea {
				width: 100%;
				min-height: 400px;
				padding: 16px;
				border: none;
				resize: vertical;
				font-family: 'IBM Plex Mono', Consolas, Monaco, monospace;
				font-size: 14px;
				line-height: 1.6;
				box-sizing: border-box;
			}

			.crispy-markdown-editor__textarea:focus {
				outline: none;
				box-shadow: none;
			}

			.crispy-markdown-editor__preview {
				padding: 16px;
				min-height: 400px;
				overflow-y: auto;
			}

			.crispy-markdown-editor__placeholder {
				color: #8c8f94;
				font-style: italic;
			}

			.crispy-markdown-editor__footer {
				padding: 8px 12px;
				border-top: 1px solid #c3c4c7;
				background: #f6f7f7;
				font-size: 12px;
				color: #50575e;
			}

			.crispy-markdown-editor__stats {
				display: inline-flex;
				gap: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Save the markdown content.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $_post   The post object (unused, required by hook signature).
	 * @return void
	 */
	public function save_markdown_content( int $post_id, \WP_Post $_post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified, not stored.
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_FIELD ] ), self::NONCE_ACTION ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if this is a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Get and sanitize the markdown content.
		// Note: We don't sanitize heavily as markdown may contain HTML.
		$markdown = isset( $_POST['crispy_markdown_content'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Intentionally allowing raw markdown.
			? wp_unslash( $_POST['crispy_markdown_content'] )
			: '';

		// Update or delete the meta.
		if ( empty( $markdown ) ) {
			delete_post_meta( $post_id, MarkdownRenderer::META_KEY );
		} else {
			update_post_meta( $post_id, MarkdownRenderer::META_KEY, $markdown );
		}
	}

	/**
	 * Get the meta box ID.
	 *
	 * @return string The metabox ID.
	 */
	public static function get_meta_box_id(): string {
		return self::METABOX_ID;
	}
}
