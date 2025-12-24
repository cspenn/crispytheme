<?php
/**
 * RSS Filter class.
 *
 * Handles RSS feed content for posts with markdown content.
 * Supports configurable full content vs excerpts in feeds.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Content;

/**
 * RSS Filter class.
 */
class RSSFilter {

	/**
	 * Option key for RSS content setting.
	 */
	public const OPTION_KEY = 'crispytheme_rss_content';

	/**
	 * RSS content mode: full content.
	 */
	public const MODE_FULL = 'full';

	/**
	 * RSS content mode: excerpts only.
	 */
	public const MODE_EXCERPT = 'excerpt';

	/**
	 * The markdown renderer instance.
	 *
	 * @var MarkdownRenderer
	 */
	private MarkdownRenderer $renderer;

	/**
	 * The excerpt generator instance.
	 *
	 * @var ExcerptGenerator
	 */
	private ExcerptGenerator $excerpt_generator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->renderer          = new MarkdownRenderer();
		$this->excerpt_generator = new ExcerptGenerator();
	}

	/**
	 * Initialize the RSS filter.
	 *
	 * @return void
	 */
	public function init(): void {
		// Filter RSS content.
		add_filter( 'the_content_feed', [ $this, 'filter_feed_content' ], 10, 2 );

		// Filter RSS excerpt.
		add_filter( 'the_excerpt_rss', [ $this, 'filter_feed_excerpt' ], 10 );
	}

	/**
	 * Filter the feed content based on settings.
	 *
	 * @param string $content   The post content.
	 * @param string $feed_type The feed type.
	 * @return string The filtered content.
	 */
	public function filter_feed_content( string $content, string $feed_type ): string {
		$post = get_post();
		if ( ! $post ) {
			return $content;
		}

		// Get markdown content.
		$markdown = $this->renderer->get_markdown_content( $post->ID );

		// Fall back to default content if no markdown.
		if ( empty( $markdown ) ) {
			return $content;
		}

		// Get the configured mode.
		$mode = $this->get_content_mode();

		if ( self::MODE_EXCERPT === $mode ) {
			return $this->excerpt_generator->generate_from_markdown( $markdown );
		}

		// Full content mode - render the markdown.
		return $this->renderer->render( $post->ID, $markdown );
	}

	/**
	 * Filter the feed excerpt.
	 *
	 * @param string $excerpt The post excerpt.
	 * @return string The filtered excerpt.
	 */
	public function filter_feed_excerpt( string $excerpt ): string {
		$post = get_post();
		if ( ! $post ) {
			return $excerpt;
		}

		// If post has a manual excerpt, use it.
		if ( ! empty( $post->post_excerpt ) ) {
			return $excerpt;
		}

		// Get markdown content.
		$markdown = $this->renderer->get_markdown_content( $post->ID );

		// Fall back to default excerpt if no markdown.
		if ( empty( $markdown ) ) {
			return $excerpt;
		}

		return $this->excerpt_generator->generate_from_markdown( $markdown );
	}

	/**
	 * Get the configured RSS content mode.
	 *
	 * @return string The content mode ('full' or 'excerpt').
	 */
	public function get_content_mode(): string {
		$mode = get_option( self::OPTION_KEY, self::MODE_FULL );

		/**
		 * Filter the RSS content mode.
		 *
		 * @param string $mode The content mode.
		 */
		$mode = apply_filters( 'crispytheme_rss_content_mode', $mode );

		// Validate the mode.
		if ( ! in_array( $mode, [ self::MODE_FULL, self::MODE_EXCERPT ], true ) ) {
			return self::MODE_FULL;
		}

		return $mode;
	}

	/**
	 * Set the RSS content mode.
	 *
	 * @param string $mode The content mode ('full' or 'excerpt').
	 * @return bool True on success, false on failure.
	 */
	public function set_content_mode( string $mode ): bool {
		if ( ! in_array( $mode, [ self::MODE_FULL, self::MODE_EXCERPT ], true ) ) {
			return false;
		}

		return update_option( self::OPTION_KEY, $mode );
	}

	/**
	 * Get available content modes with labels.
	 *
	 * @return array<string, string> Array of mode => label pairs.
	 */
	public static function get_available_modes(): array {
		return [
			self::MODE_FULL    => __( 'Full Content', 'crispy-theme' ),
			self::MODE_EXCERPT => __( 'Excerpt Only', 'crispy-theme' ),
		];
	}
}
