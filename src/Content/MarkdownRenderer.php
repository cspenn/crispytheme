<?php
/**
 * Markdown Renderer class.
 *
 * Core engine for rendering markdown content from post meta.
 * Hooks into the_content filter and implements caching.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Content;

use CrispyTheme\Cache\TransientCache;
use CrispyTheme\Parser\ParserFactory;
use CrispyTheme\Parser\ParserInterface;

/**
 * Markdown Renderer class.
 */
class MarkdownRenderer {

	/**
	 * The meta key for markdown content.
	 */
	public const META_KEY = '_markdown_content';

	/**
	 * CSS class for the markdown container.
	 */
	private const CONTAINER_CLASS = 'markdown-body';

	/**
	 * The cache instance.
	 *
	 * @var TransientCache
	 */
	private TransientCache $cache;

	/**
	 * The parser instance.
	 *
	 * @var ParserInterface|null
	 */
	private ?ParserInterface $parser = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cache = new TransientCache();
	}

	/**
	 * Initialize the renderer.
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook into the_content filter.
		add_filter( 'the_content', [ $this, 'render_content' ], 10 );

		// Clear cache when post is saved.
		add_action( 'save_post', [ $this, 'clear_post_cache' ], 10, 2 );

		// Clear cache when post meta is updated.
		add_action( 'updated_post_meta', [ $this, 'on_meta_update' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'on_meta_delete' ], 10, 4 );
	}

	/**
	 * Render markdown content for the current post.
	 *
	 * @param string $content The default post content.
	 * @return string The rendered content.
	 */
	public function render_content( string $content ): string {
		// Only process on singular views.
		if ( ! is_singular() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post ) {
			return $content;
		}

		// Check if this post type supports the editor.
		if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
			return $content;
		}

		// Get markdown content from post meta.
		$markdown = $this->get_markdown_content( $post->ID );

		// Fall back to default content if no markdown.
		if ( empty( $markdown ) ) {
			return $content;
		}

		// Render the markdown.
		return $this->render( $post->ID, $markdown );
	}

	/**
	 * Render markdown to HTML with caching.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $markdown The markdown content.
	 * @return string The rendered HTML.
	 */
	public function render( int $post_id, string $markdown ): string {
		// Generate cache key.
		$cache_key = $this->cache->generate_key( $post_id, $markdown );

		// Try to get from cache.
		$cached_html = $this->cache->get( $cache_key );
		if ( false !== $cached_html ) {
			return $cached_html;
		}

		// Parse the markdown.
		$parser = $this->get_parser();
		$html   = $parser->parse( $markdown );

		// Wrap in container div.
		$html = $this->wrap_content( $html );

		// Cache the result.
		$this->cache->set( $cache_key, $html );

		return $html;
	}

	/**
	 * Get markdown content for a post.
	 *
	 * @param int $post_id The post ID.
	 * @return string The markdown content, or empty string if not found.
	 */
	public function get_markdown_content( int $post_id ): string {
		$markdown = get_post_meta( $post_id, self::META_KEY, true );

		if ( ! is_string( $markdown ) ) {
			return '';
		}

		return $markdown;
	}

	/**
	 * Set markdown content for a post.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $markdown The markdown content.
	 * @return bool True on success, false on failure.
	 */
	public function set_markdown_content( int $post_id, string $markdown ): bool {
		return (bool) update_post_meta( $post_id, self::META_KEY, $markdown );
	}

	/**
	 * Clear cache for a specific post.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 * @return void
	 */
	public function clear_post_cache( int $post_id, \WP_Post $post ): void {
		// Don't clear cache for revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$this->cache->delete_for_post( $post_id );
	}

	/**
	 * Handle post meta update.
	 *
	 * @param int    $meta_id    The meta ID.
	 * @param int    $post_id    The post ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The meta value.
	 * @return void
	 */
	public function on_meta_update( int $meta_id, int $post_id, string $meta_key, mixed $meta_value ): void {
		if ( self::META_KEY === $meta_key ) {
			$this->cache->delete_for_post( $post_id );
		}
	}

	/**
	 * Handle post meta deletion.
	 *
	 * @param string[] $meta_ids   The meta IDs.
	 * @param int      $post_id    The post ID.
	 * @param string   $meta_key   The meta key.
	 * @param mixed    $meta_value The meta value.
	 * @return void
	 */
	public function on_meta_delete( array $meta_ids, int $post_id, string $meta_key, mixed $meta_value ): void {
		if ( self::META_KEY === $meta_key ) {
			$this->cache->delete_for_post( $post_id );
		}
	}

	/**
	 * Get the parser instance.
	 *
	 * @return ParserInterface The parser.
	 */
	private function get_parser(): ParserInterface {
		if ( null === $this->parser ) {
			$this->parser = ParserFactory::create();
		}

		return $this->parser;
	}

	/**
	 * Wrap content in the markdown container.
	 *
	 * @param string $html The HTML content.
	 * @return string The wrapped content.
	 */
	private function wrap_content( string $html ): string {
		/**
		 * Filter the container class for markdown content.
		 *
		 * @param string $class The container class.
		 */
		$class = apply_filters( 'crispytheme_markdown_container_class', self::CONTAINER_CLASS );

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( $class ),
			$html
		);
	}

	/**
	 * Parse markdown without caching (for preview purposes).
	 *
	 * @param string $markdown The markdown content.
	 * @return string The rendered HTML.
	 */
	public function parse_without_cache( string $markdown ): string {
		$parser = $this->get_parser();
		$html   = $parser->parse( $markdown );

		return $this->wrap_content( $html );
	}

	/**
	 * Check if a post has markdown content.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if the post has markdown content.
	 */
	public function has_markdown_content( int $post_id ): bool {
		$markdown = $this->get_markdown_content( $post_id );

		return ! empty( $markdown );
	}
}
