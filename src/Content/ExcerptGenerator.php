<?php
/**
 * Excerpt Generator class.
 *
 * Auto-generates post excerpts from markdown content.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Content;

use CrispyTheme\Parser\ParserFactory;

/**
 * Excerpt Generator class.
 */
class ExcerptGenerator {

	/**
	 * Default number of words for excerpts.
	 */
	private const DEFAULT_WORD_COUNT = 55;

	/**
	 * Default excerpt more text.
	 */
	private const DEFAULT_MORE_TEXT = '&hellip;';

	/**
	 * Initialize the excerpt generator.
	 *
	 * @return void
	 */
	public function init(): void {
		// Filter the excerpt for posts with markdown content.
		add_filter( 'get_the_excerpt', [ $this, 'generate_excerpt' ], 10, 2 );

		// Filter excerpt length.
		add_filter( 'excerpt_length', [ $this, 'get_excerpt_length' ], 10 );

		// Filter excerpt more text.
		add_filter( 'excerpt_more', [ $this, 'get_excerpt_more' ], 10 );
	}

	/**
	 * Generate excerpt from markdown content.
	 *
	 * @param string   $excerpt The current excerpt.
	 * @param \WP_Post $post    The post object.
	 * @return string The generated excerpt.
	 */
	public function generate_excerpt( string $excerpt, \WP_Post $post ): string {
		// If the post already has a manual excerpt, use it.
		if ( ! empty( $post->post_excerpt ) ) {
			return $excerpt;
		}

		// Get markdown content.
		$markdown = get_post_meta( $post->ID, MarkdownRenderer::META_KEY, true );

		// Fall back to default excerpt if no markdown.
		if ( empty( $markdown ) || ! is_string( $markdown ) ) {
			return $excerpt;
		}

		// Generate excerpt from markdown.
		return $this->generate_from_markdown( $markdown );
	}

	/**
	 * Generate excerpt from markdown text.
	 *
	 * @param string   $markdown   The markdown content.
	 * @param int|null $word_count Optional. Number of words for excerpt.
	 * @return string The generated excerpt.
	 */
	public function generate_from_markdown( string $markdown, ?int $word_count = null ): string {
		// Parse markdown to HTML.
		$parser = ParserFactory::create();
		$html   = $parser->parse( $markdown );

		return $this->generate_from_html( $html, $word_count );
	}

	/**
	 * Generate excerpt from HTML.
	 *
	 * @param string   $html       The HTML content.
	 * @param int|null $word_count Optional. Number of words for excerpt.
	 * @return string The generated excerpt.
	 */
	public function generate_from_html( string $html, ?int $word_count = null ): string {
		if ( null === $word_count ) {
			/**
			 * Filter the excerpt word count.
			 *
			 * @param int $word_count Number of words.
			 */
			$word_count = apply_filters( 'crispytheme_excerpt_length', self::DEFAULT_WORD_COUNT );
		}

		// Strip HTML tags.
		$text = wp_strip_all_tags( $html );

		// Normalize whitespace.
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		// Handle empty content.
		if ( empty( $text ) ) {
			return '';
		}

		// Trim to word count.
		$excerpt = wp_trim_words( $text, $word_count, '' );

		// Add more text if content was trimmed.
		if ( str_word_count( $text ) > $word_count ) {
			/**
			 * Filter the excerpt more text.
			 *
			 * @param string $more_text The more text.
			 */
			$more_text = apply_filters( 'crispytheme_excerpt_more', self::DEFAULT_MORE_TEXT );
			$excerpt  .= $more_text;
		}

		return $excerpt;
	}

	/**
	 * Get the configured excerpt length.
	 *
	 * @param int $length The current excerpt length.
	 * @return int The filtered excerpt length.
	 */
	public function get_excerpt_length( int $length ): int {
		/**
		 * Filter the excerpt word count.
		 *
		 * @param int $word_count Number of words.
		 */
		return apply_filters( 'crispytheme_excerpt_length', self::DEFAULT_WORD_COUNT );
	}

	/**
	 * Get the configured excerpt more text.
	 *
	 * @param string $more_text The current more text.
	 * @return string The filtered more text.
	 */
	public function get_excerpt_more( string $more_text ): string {
		/**
		 * Filter the excerpt more text.
		 *
		 * @param string $more_text The more text.
		 */
		return apply_filters( 'crispytheme_excerpt_more', self::DEFAULT_MORE_TEXT );
	}

	/**
	 * Generate excerpt with sentence boundary.
	 *
	 * Tries to end the excerpt at a sentence boundary for better readability.
	 *
	 * @param string $markdown   The markdown content.
	 * @param int    $min_length Minimum length in characters.
	 * @param int    $max_length Maximum length in characters.
	 * @return string The generated excerpt.
	 */
	public function generate_with_sentence_boundary(
		string $markdown,
		int $min_length = 100,
		int $max_length = 300
	): string {
		// Parse markdown to HTML.
		$parser = ParserFactory::create();
		$html   = $parser->parse( $markdown );

		// Strip HTML tags.
		$text = wp_strip_all_tags( $html );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		// If text is shorter than minimum, return as-is.
		if ( strlen( $text ) <= $min_length ) {
			return $text;
		}

		// Find the last sentence boundary within max_length.
		$excerpt = substr( $text, 0, $max_length );

		// Look for sentence endings.
		$last_period   = strrpos( $excerpt, '. ' );
		$last_exclaim  = strrpos( $excerpt, '! ' );
		$last_question = strrpos( $excerpt, '? ' );

		// Find the latest sentence ending.
		$last_sentence = max(
			false !== $last_period ? $last_period : 0,
			false !== $last_exclaim ? $last_exclaim : 0,
			false !== $last_question ? $last_question : 0
		);

		// Use sentence boundary if it's after minimum length.
		if ( $last_sentence >= $min_length ) {
			return substr( $text, 0, $last_sentence + 1 );
		}

		// Fall back to word boundary.
		return wp_trim_words( $text, 55, self::DEFAULT_MORE_TEXT );
	}
}
