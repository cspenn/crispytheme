<?php
/**
 * Parser Factory.
 *
 * Creates the appropriate markdown parser based on configuration.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Parser;

use Parsedown;
use ParsedownExtra;

/**
 * Parser Factory class.
 */
class ParserFactory {

	/**
	 * Parser type: basic Parsedown.
	 */
	public const TYPE_BASIC = 'basic';

	/**
	 * Parser type: Parsedown Extra.
	 */
	public const TYPE_EXTRA = 'extra';

	/**
	 * Create a parser instance.
	 *
	 * @param string|null $type The parser type ('basic' or 'extra'). Defaults to filtered value.
	 * @return ParserInterface The parser instance.
	 */
	public static function create( ?string $type = null ): ParserInterface {
		if ( null === $type ) {
			/**
			 * Filter the parser type to use.
			 *
			 * @param string $type The parser type. Default 'extra'.
			 */
			$type = apply_filters( 'crispytheme_parser_type', self::TYPE_EXTRA );
		}

		/**
		 * Filter whether to allow unsafe HTML in markdown output.
		 *
		 * @param bool $allow Whether to allow unsafe HTML. Default true (trust authors).
		 */
		$allow_unsafe_html = apply_filters( 'crispytheme_allow_unsafe_html', true );

		$parser = match ( $type ) {
			self::TYPE_BASIC => new ParsedownWrapper( new Parsedown() ),
			self::TYPE_EXTRA => new ParsedownExtraWrapper( new ParsedownExtra() ),
			default          => new ParsedownExtraWrapper( new ParsedownExtra() ),
		};

		$parser->set_allow_unsafe_html( $allow_unsafe_html );

		return $parser;
	}
}
