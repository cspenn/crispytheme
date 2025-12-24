<?php
/**
 * Parser Interface.
 *
 * Defines the contract for markdown parsers used by the theme.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Parser;

/**
 * Parser Interface.
 */
interface ParserInterface {

	/**
	 * Parse markdown text into HTML.
	 *
	 * @param string $markdown The markdown text to parse.
	 * @return string The parsed HTML.
	 */
	public function parse( string $markdown ): string;

	/**
	 * Set whether to allow unsafe HTML in the output.
	 *
	 * @param bool $allow Whether to allow unsafe HTML.
	 * @return void
	 */
	public function set_allow_unsafe_html( bool $allow ): void;

	/**
	 * Check if the parser allows unsafe HTML.
	 *
	 * @return bool True if unsafe HTML is allowed.
	 */
	public function allows_unsafe_html(): bool;
}
