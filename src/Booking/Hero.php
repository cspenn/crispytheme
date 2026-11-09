<?php
/**
 * First screen of the home page and the booking page.
 *
 * One headline across the grid, one sentence, then the menu card of the audience's main problem beside one
 * photograph. The chosen problem shows its answer (promise, talk, room) on the photograph's lower edge.
 * Both pages call this class, so they cannot drift apart (design-taste-frontend, direction "split stage",
 * hero "menu column", 2026-10-26).
 *
 * @package CrispyTheme
 * @since 2.3.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * First screen renderer.
 */
final class Hero {

	/**
	 * The hero quote: the second sentence of the second paragraph of the LPA letter (Clark Mulligan, President,
	 * LPA, permission stated by Penn, 2026-10-22). It must stay word for word.
	 */
	public const QUOTE = 'The content you delivered to our members was absolutely spot on.';

	/**
	 * Alternative text of the first photograph.
	 */
	public const ALT = 'Christopher Penn in a grey suit speaks on a lit stage beside the MAICON sign.';

	/**
	 * Render the first screen.
	 *
	 * @param string $title_html Headline markup (escaped by the caller).
	 * @param string $sub        Supporting sentence, plain text.
	 * @param string $fees_href  Address of the fees block.
	 * @param bool   $first_person Write the quote caption in the voice of the booking page.
	 * @return string
	 */
	public static function render( string $title_html, string $sub, string $fees_href, bool $first_person = false ): string {
		$actions = '<button class="bp-btn" type="submit">Check my dates</button>'
			. '<a class="bp-link" href="' . esc_url( $fees_href ) . '">Fees and terms</a>';
		$pic     = BookingPage::img(
			'hero-43',
			self::ALT,
			'fetchpriority="high" loading="eager" sizes="(max-width: 1000px) 100vw, 50vw"'
		);

		return '<section class="hero" aria-labelledby="bp-title">'
			. '<div class="bp-in hero__head">'
			. '<h1 id="bp-title" class="hero__title">' . $title_html . '</h1>'
			. '</div>'
			. '<div class="bp-in hero__grid">'
			. '<div class="hero__text">'
			. ProblemPicker::menu( $actions )
			. '</div>'
			. '<figure class="hero__pic">' . $pic . '</figure>'
			. '</div>'
			. '<div class="hero__band"><div class="bp-in hero__band-in">'
			. '<p class="hero__sub speakable-summary">' . esc_html( $sub ) . '</p>'
			. self::quote( $first_person )
			. '</div></div>'
			. '</section>';
	}

	/**
	 * The quote of the band under the hero: one whole sentence of the LPA letter, word for word, with its source
	 * and the repeat booking, so a date before 2025 never shows without one (planner test b).
	 *
	 * @param bool $first_person Write in the voice of the booking page.
	 * @return string
	 */
	private static function quote( bool $first_person ): string {
		$again = $first_person ? 'LPA booked me again in September 2026.' : 'LPA booked Christopher Penn again in September 2026.';

		return '<figure class="hero__quote">'
			. '<blockquote><p>' . esc_html( self::QUOTE ) . '</p></blockquote>'
			. '<figcaption><span>Clark Mulligan, President, LPA, ' . esc_html( BookingFacts::get( 'quote_date' ) ) . '.</span> <span>' . esc_html( $again ) . '</span></figcaption>'
			. '</figure>';
	}
}
