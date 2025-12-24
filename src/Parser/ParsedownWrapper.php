<?php
/**
 * Parsedown Wrapper class.
 *
 * Wraps the basic Parsedown class to implement our interface.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Parser;

use Parsedown;

/**
 * Parsedown Wrapper class.
 */
class ParsedownWrapper implements ParserInterface {

	/**
	 * The Parsedown instance.
	 *
	 * @var Parsedown
	 */
	private Parsedown $parsedown;

	/**
	 * Whether unsafe HTML is allowed.
	 *
	 * @var bool
	 */
	private bool $allow_unsafe_html = true;

	/**
	 * Constructor.
	 *
	 * @param Parsedown $parsedown The Parsedown instance.
	 */
	public function __construct( Parsedown $parsedown ) {
		$this->parsedown = $parsedown;
	}

	/**
	 * Parse markdown text into HTML.
	 *
	 * @param string $markdown The markdown text to parse.
	 * @return string The parsed HTML.
	 */
	public function parse( string $markdown ): string {
		// Configure safe mode based on settings.
		$this->parsedown->setSafeMode( ! $this->allow_unsafe_html );
		$this->parsedown->setMarkupEscaped( ! $this->allow_unsafe_html );

		return $this->parsedown->text( $markdown );
	}

	/**
	 * Set whether to allow unsafe HTML in the output.
	 *
	 * @param bool $allow Whether to allow unsafe HTML.
	 * @return void
	 */
	public function set_allow_unsafe_html( bool $allow ): void {
		$this->allow_unsafe_html = $allow;
	}

	/**
	 * Check if the parser allows unsafe HTML.
	 *
	 * @return bool True if unsafe HTML is allowed.
	 */
	public function allows_unsafe_html(): bool {
		return $this->allow_unsafe_html;
	}
}
