<?php
/**
 * HTML to Markdown Converter class.
 *
 * Converts HTML content to Markdown for legacy post migration.
 * Used when editing posts that have HTML in post_content but no _markdown_content meta.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Content;

use League\HTMLToMarkdown\HtmlConverter;

/**
 * HTML to Markdown Converter class.
 */
class HtmlToMarkdownConverter {

	/**
	 * The HTML to Markdown converter instance.
	 *
	 * @var HtmlConverter
	 */
	private HtmlConverter $converter;

	/**
	 * Initialize the converter with WordPress-optimized options.
	 */
	public function __construct() {
		$this->converter = new HtmlConverter( $this->get_converter_options() );
	}

	/**
	 * Convert HTML to Markdown.
	 *
	 * Preprocesses WordPress-specific HTML (Gutenberg blocks, shortcodes)
	 * before conversion.
	 *
	 * @param string $html The HTML content to convert.
	 * @return string The converted Markdown content.
	 */
	public function convert( string $html ): string {
		// Handle empty input.
		$html = trim( $html );
		if ( empty( $html ) ) {
			return '';
		}

		try {
			// Strip Gutenberg block comments before conversion.
			$html = $this->strip_gutenberg_comments( $html );

			// Convert HTML to Markdown.
			$markdown = $this->converter->convert( $html );

			// Clean up excessive whitespace.
			$markdown = $this->normalize_whitespace( $markdown );

			// Unescape markdown characters that were over-escaped by the library.
			$markdown = $this->unescape_markdown_syntax( $markdown );

			/**
			 * Filter the converted Markdown content.
			 *
			 * @param string $markdown The converted Markdown.
			 * @param string $html The original HTML content.
			 */
			return apply_filters( 'crispytheme_html_to_markdown_result', $markdown, $html );
		} catch ( \Exception $e ) {
			// Return empty string on failure; content remains as HTML in post_content.
			return '';
		}
	}

	/**
	 * Check if a post is a legacy post that needs conversion.
	 *
	 * A post is considered "legacy" if it has HTML content in post_content
	 * but no _markdown_content meta stored.
	 *
	 * @param int $post_id The post ID to check.
	 * @return bool True if the post is a legacy post needing conversion.
	 */
	public function is_legacy_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		// Check if post has HTML content.
		if ( empty( trim( $post->post_content ) ) ) {
			return false;
		}

		// Check if _markdown_content meta exists.
		$markdown = get_post_meta( $post_id, MarkdownRenderer::META_KEY, true );
		return empty( $markdown );
	}

	/**
	 * Get the converter options for WordPress content.
	 *
	 * @return array<string, mixed> The converter options.
	 */
	private function get_converter_options(): array {
		return [
			'strip_tags'        => true,              // Strip unsupported HTML tags.
			'remove_nodes'      => 'script style',    // Remove dangerous elements.
			'hard_break'        => false,             // Use soft line breaks.
			'preserve_comments' => false,             // Strip HTML comments.
			'header_style'      => 'atx',             // Use # style headers.
			'bold_style'        => '**',              // Use ** for bold.
			'italic_style'      => '*',               // Use * for italic.
			'list_item_style'   => '-',               // Use - for list items.
			'use_autolinks'     => true,              // Use <url> autolink style.
		];
	}

	/**
	 * Strip Gutenberg block comments from HTML.
	 *
	 * Removes WordPress block editor comments like <!-- wp:paragraph -->
	 * while preserving the inner HTML content.
	 *
	 * @param string $html The HTML content with Gutenberg comments.
	 * @return string The HTML content without Gutenberg comments.
	 */
	private function strip_gutenberg_comments( string $html ): string {
		// Remove opening block comments like <!-- wp:block-name {"attrs":"values"} -->.
		$html = preg_replace( '/<!--\s*wp:[^>]*-->/s', '', $html );

		// Remove closing block comments like <!-- /wp:block-name -->.
		$html = preg_replace( '/<!--\s*\/wp:[^>]*-->/s', '', $html );

		return is_string( $html ) ? $html : '';
	}

	/**
	 * Normalize whitespace and ensure proper block element spacing.
	 *
	 * Ensures blank lines exist between block-level Markdown elements
	 * while reducing excessive whitespace.
	 *
	 * @param string $markdown The Markdown content to normalize.
	 * @return string The normalized Markdown content.
	 */
	private function normalize_whitespace( string $markdown ): string {
		// First, normalize line endings to \n.
		$markdown = str_replace( [ "\r\n", "\r" ], "\n", $markdown );

		// Ensure blank line before headers (# through ######).
		// Match: single newline followed by # (not already preceded by blank line).
		$markdown = preg_replace( '/(?<!\n)\n(#{1,6}\s)/m', "\n\n$1", $markdown );

		// Ensure blank line before horizontal rules (---, ***, ___).
		$markdown = preg_replace( '/(?<!\n)\n([-*_]{3,})\n/m', "\n\n$1\n", $markdown );

		// Ensure blank line before list items at start of list.
		// Only add spacing before first list item, not between list items.
		$markdown = preg_replace( '/(?<!\n)\n([-*+]\s|\d+\.\s)(?![-*+]\s|\d+\.\s)/m', "\n\n$1", $markdown );

		// Ensure blank line before blockquotes.
		$markdown = preg_replace( '/(?<!\n)\n(>\s)/m', "\n\n$1", $markdown );

		// Ensure blank line before code blocks (indented or fenced).
		$markdown = preg_replace( '/(?<!\n)\n(```|    )/m', "\n\n$1", $markdown );

		// Now reduce excessive newlines (3+ to exactly 2).
		$markdown = is_string( $markdown ) ? preg_replace( '/\n{3,}/', "\n\n", $markdown ) : '';

		// Trim leading/trailing whitespace.
		return is_string( $markdown ) ? trim( $markdown ) : '';
	}

	/**
	 * Unescape Markdown syntax that was over-escaped by the converter.
	 *
	 * The html-to-markdown library escapes special characters to prevent
	 * Markdown syntax injection, but this breaks legitimate WordPress
	 * shortcodes and Markdown formatting.
	 *
	 * @param string $markdown The converted Markdown with escaped characters.
	 * @return string The Markdown with proper syntax unescaped.
	 */
	private function unescape_markdown_syntax( string $markdown ): string {
		// Unescape WordPress shortcodes: \[shortcode\] → [shortcode].
		$markdown = preg_replace( '/\\\\\[([^\]]+)\\\\\]/', '[$1]', $markdown );

		// Unescape Markdown emphasis: \*\*text\*\* → **text**, \*text\* → *text*.
		$markdown = is_string( $markdown ) ? str_replace( '\\*', '*', $markdown ) : '';

		// Unescape Markdown underscores: \_text\_ → _text_.
		$markdown = str_replace( '\\_', '_', $markdown );

		// Unescape Markdown images: \! → !.
		$markdown = str_replace( '\\!', '!', $markdown );

		return $markdown;
	}
}
