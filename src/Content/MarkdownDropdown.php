<?php
/**
 * Markdown Dropdown component.
 *
 * Provides "Copy page" and "View as Markdown" functionality
 * for posts, inspired by Anthropic's documentation UI.
 *
 * @package CrispyTheme
 * @since 1.0.9
 */

declare(strict_types=1);

namespace CrispyTheme\Content;

/**
 * Markdown Dropdown class.
 *
 * Renders a dropdown menu allowing users to copy page content
 * as Markdown or view the raw Markdown source.
 */
class MarkdownDropdown {

	/**
	 * Query variable for raw markdown requests.
	 */
	public const QUERY_VAR = 'crispy_raw';

	/**
	 * Nonce action for AJAX requests.
	 */
	private const NONCE_ACTION = 'crispy_markdown_dropdown';

	/**
	 * Nonce action for analytics tracking.
	 */
	private const TRACKING_NONCE_ACTION = 'crispy_markdown_analytics';

	/**
	 * Option name for aggregate analytics.
	 */
	private const ANALYTICS_OPTION = 'crispytheme_markdown_analytics';

	/**
	 * Meta key for copy count.
	 */
	public const META_COPY_COUNT = '_crispy_markdown_copy_count';

	/**
	 * Meta key for view count.
	 */
	public const META_VIEW_COUNT = '_crispy_markdown_view_count';

	/**
	 * HTML to Markdown converter instance.
	 *
	 * @var HtmlToMarkdownConverter|null
	 */
	private ?HtmlToMarkdownConverter $converter = null;

	/**
	 * Initialize the dropdown component.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register query var and handler for raw markdown requests.
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_raw_request' ] );

		// Inject dropdown into content.
		add_filter( 'the_content', [ $this, 'prepend_dropdown' ], 5 );

		// AJAX handler for copy functionality.
		add_action( 'wp_ajax_crispy_get_markdown', [ $this, 'ajax_get_markdown' ] );
		add_action( 'wp_ajax_nopriv_crispy_get_markdown', [ $this, 'ajax_get_markdown' ] );

		// AJAX handler for analytics tracking.
		add_action( 'wp_ajax_crispy_track_markdown_action', [ $this, 'ajax_track_action' ] );
		add_action( 'wp_ajax_nopriv_crispy_track_markdown_action', [ $this, 'ajax_track_action' ] );
	}

	/**
	 * Register custom query variable.
	 *
	 * @param array<string> $vars Existing query vars.
	 * @return array<string> Modified query vars.
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Handle raw markdown request.
	 *
	 * Serves markdown content as plain text when /raw/ endpoint is accessed.
	 *
	 * @return void
	 */
	public function handle_raw_request(): void {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			wp_die(
				esc_html__( 'Post not found.', 'crispy-theme' ),
				esc_html__( 'Not Found', 'crispy-theme' ),
				[ 'response' => 404 ]
			);
		}

		$markdown = $this->get_markdown_for_post( $post_id );
		if ( empty( $markdown ) ) {
			wp_die(
				esc_html__( 'No content available for this post.', 'crispy-theme' ),
				esc_html__( 'Not Found', 'crispy-theme' ),
				[ 'response' => 404 ]
			);
		}

		$post   = get_post( $post_id );
		$output = $this->build_markdown_output( $post, $markdown );

		// Send appropriate headers.
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $post->post_name ) . '.md"' );
		header( 'X-Robots-Tag: noindex' );
		header( 'Cache-Control: public, max-age=3600' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain text output.
		echo $output;
		exit;
	}

	/**
	 * Prepend dropdown to post content.
	 *
	 * @param string $content The post content.
	 * @return string Modified content with dropdown.
	 */
	public function prepend_dropdown( string $content ): string {
		// Only show on singular views.
		if ( ! is_singular() ) {
			return $content;
		}

		// Only show on supported post types.
		$post = get_post();
		if ( ! $post || ! post_type_supports( $post->post_type, 'editor' ) ) {
			return $content;
		}

		// Skip if in feed or REST request.
		if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}

		$dropdown = $this->render_dropdown( $post->ID );
		return $dropdown . $content;
	}

	/**
	 * Render the dropdown HTML.
	 *
	 * @param int $post_id The post ID.
	 * @return string The dropdown HTML.
	 */
	public function render_dropdown( int $post_id ): string {
		$raw_url      = add_query_arg( self::QUERY_VAR, '1', get_permalink( $post_id ) );
		$has_markdown = $this->has_native_markdown( $post_id );

		ob_start();
		?>
		<div class="crispy-markdown-dropdown" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
			<button
				type="button"
				class="crispy-markdown-dropdown__trigger"
				aria-expanded="false"
				aria-haspopup="menu"
				aria-label="<?php esc_attr_e( 'AI tools for this page', 'crispy-theme' ); ?>"
			>
				<span class="crispy-markdown-dropdown__label"><?php esc_html_e( 'AI Tools for This Page', 'crispy-theme' ); ?></span>
				<svg class="crispy-markdown-dropdown__chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M3 5l3 3 3-3"/>
				</svg>
			</button>

			<div class="crispy-markdown-dropdown__menu" role="menu" hidden>
				<button
					type="button"
					class="crispy-markdown-dropdown__item"
					role="menuitem"
					data-action="copy"
				>
					<svg class="crispy-markdown-dropdown__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
						<path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
					</svg>
					<span class="crispy-markdown-dropdown__text">
						<span class="crispy-markdown-dropdown__title"><?php esc_html_e( 'Copy page', 'crispy-theme' ); ?></span>
						<span class="crispy-markdown-dropdown__description"><?php esc_html_e( 'Copy as Markdown for LLMs', 'crispy-theme' ); ?></span>
					</span>
				</button>

				<a
					href="<?php echo esc_url( $raw_url ); ?>"
					class="crispy-markdown-dropdown__item"
					role="menuitem"
					target="_blank"
					rel="noopener"
					data-action="view"
				>
					<svg class="crispy-markdown-dropdown__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
						<polyline points="14 2 14 8 20 8"/>
						<line x1="16" y1="13" x2="8" y2="13"/>
						<line x1="16" y1="17" x2="8" y2="17"/>
						<polyline points="10 9 9 9 8 9"/>
					</svg>
					<span class="crispy-markdown-dropdown__text">
						<span class="crispy-markdown-dropdown__title"><?php esc_html_e( 'View as Markdown', 'crispy-theme' ); ?></span>
						<span class="crispy-markdown-dropdown__description">
							<?php
							if ( $has_markdown ) {
								esc_html_e( 'View original Markdown source', 'crispy-theme' );
							} else {
								esc_html_e( 'View converted Markdown', 'crispy-theme' );
							}
							?>
						</span>
					</span>
					<svg class="crispy-markdown-dropdown__external" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
						<polyline points="15 3 21 3 21 9"/>
						<line x1="10" y1="14" x2="21" y2="3"/>
					</svg>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle AJAX request for markdown content.
	 *
	 * @return void
	 */
	public function ajax_get_markdown(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
		}

		// Validate post ID.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'Invalid post ID' ], 400 );
		}

		// Check post exists and is viewable.
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_send_json_error( [ 'message' => 'Post not found' ], 404 );
		}

		$markdown = $this->get_markdown_for_post( $post_id );
		if ( empty( $markdown ) ) {
			wp_send_json_error( [ 'message' => 'No content available' ], 404 );
		}

		$output = $this->build_markdown_output( $post, $markdown );

		wp_send_json_success( [ 'markdown' => $output ] );
	}

	/**
	 * Get markdown content for a post.
	 *
	 * First tries native markdown, then converts HTML if needed.
	 *
	 * @param int $post_id The post ID.
	 * @return string The markdown content.
	 */
	public function get_markdown_for_post( int $post_id ): string {
		// First try native markdown from post meta.
		$markdown = get_post_meta( $post_id, MarkdownRenderer::META_KEY, true );
		if ( ! empty( $markdown ) && is_string( $markdown ) ) {
			return $markdown;
		}

		// Fall back to HTML-to-Markdown conversion.
		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return '';
		}

		return $this->get_converter()->convert( $post->post_content );
	}

	/**
	 * Check if post has native markdown content.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if post has native markdown.
	 */
	public function has_native_markdown( int $post_id ): bool {
		$markdown = get_post_meta( $post_id, MarkdownRenderer::META_KEY, true );
		return ! empty( $markdown ) && is_string( $markdown );
	}

	/**
	 * Build the full markdown output with YAML frontmatter.
	 *
	 * @param \WP_Post $post     The post object.
	 * @param string   $markdown The markdown content.
	 * @return string The complete markdown output.
	 */
	private function build_markdown_output( \WP_Post $post, string $markdown ): string {
		$author = get_the_author_meta( 'display_name', (int) $post->post_author );
		$date   = get_the_date( 'Y-m-d', $post );
		$url    = get_permalink( $post->ID );

		// Build YAML frontmatter.
		$output  = "---\n";
		$output .= 'title: "' . str_replace( '"', '\\"', $post->post_title ) . "\"\n";
		$output .= 'author: "' . str_replace( '"', '\\"', $author ) . "\"\n";
		$output .= 'date: ' . $date . "\n";
		$output .= 'url: ' . $url . "\n";

		// Add categories if any.
		$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] );
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$output .= "categories:\n";
			foreach ( $categories as $category ) {
				$output .= '  - "' . str_replace( '"', '\\"', $category ) . "\"\n";
			}
		}

		// Add tags if any.
		$tags = wp_get_post_tags( $post->ID, [ 'fields' => 'names' ] );
		if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
			$output .= "tags:\n";
			foreach ( $tags as $tag ) {
				$output .= '  - "' . str_replace( '"', '\\"', $tag ) . "\"\n";
			}
		}

		$output .= "---\n\n";

		// Add title as H1.
		$output .= '# ' . $post->post_title . "\n\n";

		// Add the markdown content with shortcodes expanded.
		$output .= $this->expand_shortcodes( $markdown );

		return $output;
	}

	/**
	 * Get or create the HTML to Markdown converter.
	 *
	 * @return HtmlToMarkdownConverter The converter instance.
	 */
	private function get_converter(): HtmlToMarkdownConverter {
		if ( null === $this->converter ) {
			$this->converter = new HtmlToMarkdownConverter();
		}
		return $this->converter;
	}

	/**
	 * Get the nonce action string.
	 *
	 * Used by Assets class for script localization.
	 *
	 * @return string The nonce action.
	 */
	public static function get_nonce_action(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * Get the tracking nonce action string.
	 *
	 * Used by Assets class for script localization.
	 *
	 * @return string The tracking nonce action.
	 */
	public static function get_tracking_nonce_action(): string {
		return self::TRACKING_NONCE_ACTION;
	}

	/**
	 * Handle AJAX request to track markdown dropdown actions.
	 *
	 * Records copy and view actions for analytics.
	 *
	 * @return void
	 */
	public function ajax_track_action(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::TRACKING_NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
		}

		// Validate post ID.
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => 'Invalid post ID' ], 400 );
		}

		// Validate action type.
		$action_type = isset( $_POST['action_type'] ) ? sanitize_key( $_POST['action_type'] ) : '';
		if ( ! in_array( $action_type, [ 'copy', 'view' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid action type' ], 400 );
		}

		// Verify post exists.
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_send_json_error( [ 'message' => 'Post not found' ], 404 );
		}

		// Increment per-post count.
		$meta_key = 'copy' === $action_type ? self::META_COPY_COUNT : self::META_VIEW_COUNT;
		$current  = (int) get_post_meta( $post_id, $meta_key, true );
		update_post_meta( $post_id, $meta_key, $current + 1 );

		// Increment aggregate count.
		$this->increment_aggregate( $action_type );

		wp_send_json_success( [ 'tracked' => true ] );
	}

	/**
	 * Increment aggregate analytics counter.
	 *
	 * @param string $action_type The action type ('copy' or 'view').
	 * @return void
	 */
	private function increment_aggregate( string $action_type ): void {
		$stats = get_option(
			self::ANALYTICS_OPTION,
			[
				'total_copies' => 0,
				'total_views'  => 0,
			]
		);

		$key = 'copy' === $action_type ? 'total_copies' : 'total_views';

		$stats[ $key ]         = ( $stats[ $key ] ?? 0 ) + 1;
		$stats['last_updated'] = current_time( 'mysql' );

		update_option( self::ANALYTICS_OPTION, $stats );
	}

	/**
	 * Get aggregate analytics stats.
	 *
	 * @return array{total_copies: int, total_views: int, last_updated: string|null} The analytics stats.
	 */
	public static function get_analytics_stats(): array {
		$defaults = [
			'total_copies' => 0,
			'total_views'  => 0,
			'last_updated' => null,
		];

		$stats = get_option( self::ANALYTICS_OPTION, $defaults );

		return array_merge( $defaults, $stats );
	}

	/**
	 * Get top posts by markdown dropdown usage.
	 *
	 * @param int $limit Maximum number of posts to return.
	 * @return array<array{ID: int, post_title: string, post_type: string, copy_count: int, view_count: int, total_count: int}> Array of post data.
	 */
	public static function get_top_posts( int $limit = 10 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom meta query for analytics.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_type,
						COALESCE(mc.meta_value, 0) as copy_count,
						COALESCE(mv.meta_value, 0) as view_count,
						(COALESCE(mc.meta_value, 0) + COALESCE(mv.meta_value, 0)) as total_count
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} mc ON p.ID = mc.post_id AND mc.meta_key = %s
				 LEFT JOIN {$wpdb->postmeta} mv ON p.ID = mv.post_id AND mv.meta_key = %s
				 WHERE p.post_status = 'publish'
				 AND (mc.meta_value IS NOT NULL OR mv.meta_value IS NOT NULL)
				 ORDER BY total_count DESC
				 LIMIT %d",
				self::META_COPY_COUNT,
				self::META_VIEW_COUNT,
				$limit
			),
			ARRAY_A
		);

		if ( ! $results ) {
			return [];
		}

		// Cast numeric values.
		return array_map(
			static function ( array $row ): array {
				$row['ID']          = (int) $row['ID'];
				$row['copy_count']  = (int) $row['copy_count'];
				$row['view_count']  = (int) $row['view_count'];
				$row['total_count'] = (int) $row['total_count'];
				return $row;
			},
			$results
		);
	}


	/**
	 * Get shortcode replacements from theme options.
	 *
	 * @return array<string, string> Shortcode => replacement pairs.
	 */
	private function get_shortcode_replacements(): array {
		$option = get_option( 'crispytheme_markdown_shortcodes', '' );

		if ( empty( $option ) ) {
			return [];
		}

		$replacements = [];
		$lines        = explode( "\n", $option );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( empty( $line ) || strpos( $line, '=' ) === false ) {
				continue;
			}

			// Split on first = only (replacement may contain =).
			$parts = explode( '=', $line, 2 );

			if ( count( $parts ) === 2 ) {
				$shortcode   = trim( $parts[0] );
				$replacement = trim( $parts[1] );

				if ( ! empty( $shortcode ) ) {
					$replacements[ $shortcode ] = $replacement;
				}
			}
		}

		return $replacements;
	}

	/**
	 * Expand shortcodes in markdown content.
	 *
	 * @param string $content The markdown content.
	 * @return string Content with shortcodes expanded.
	 */
	private function expand_shortcodes( string $content ): string {
		$replacements = $this->get_shortcode_replacements();

		foreach ( $replacements as $shortcode => $replacement ) {
			$content = str_replace( $shortcode, $replacement, $content );
		}

		return $content;
	}
}
