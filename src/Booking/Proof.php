<?php
/**
 * Proof blocks for the home page and the booking page.
 *
 * Two blocks stay apart (Penn, 2026-10-26). "Recent rooms" lists rooms that Penn led in the last 18
 * months, each with month and year. "Also spoken at" lists older recognized stages. A stage date before
 * 2025 shows outside the second block only beside a repeat booking. Katie-led and shared rooms never
 * appear (plan risk 17). Source of every row: docs/client-context.md and data/contracts/engagements.csv.
 *
 * @package CrispyTheme
 * @since 2.2.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Proof blocks class.
 */
final class Proof {

	/**
	 * Reply promise (Penn, 2026-10-25): business days only, Eastern Time.
	 */
	public const REPLY = 'Trust Insights staff reply within one business day (Eastern Time).';

	/**
	 * Sentence about event promotion. It covers a full-fee keynote only and states no cadence and no count
	 * (Penn, 2026-10-26).
	 */
	public const PROMOTION = 'A full-fee keynote includes promotion of your event: newsletter inclusion, social media posts, and a promotional video clip.';

	/**
	 * Recent rooms, newest first. Keys: when (YYYY-MM), room, what, again (repeat booking text or empty).
	 *
	 * @return array<int, array{when: string, room: string, what: string, again: string}>
	 */
	public static function rooms(): array {
		return [
			[
				'when'  => '2026-10',
				'room'  => 'MAICON',
				'what'  => 'Session, Build an Analytics Powerhouse with AI',
				'again' => '',
			],
			[
				'when'  => '2026-09',
				'room'  => 'Spotlight Summit',
				'what'  => 'Keynote',
				'again' => '',
			],
			[
				'when'  => '2026-09',
				'room'  => 'LPA',
				'what'  => 'Keynote and session',
				'again' => 'Booked again: April 2024 and September 2026',
			],
			[
				'when'  => '2026-05',
				'room'  => 'MAICON, AI for Writers Summit',
				'what'  => 'Keynote, virtual',
				'again' => '',
			],
			[
				'when'  => '2026-04',
				'room'  => 'Self Storage Institute',
				'what'  => 'Keynote',
				'again' => '',
			],
			[
				'when'  => '2026-03',
				'room'  => 'Harvard Federal Credit Union',
				'what'  => 'Keynote and workshop',
				'again' => '',
			],
			[
				'when'  => '2026-02',
				'room'  => 'Tourism Industry Association of Alberta, Alberta Tourism Advocacy Summit',
				'what'  => 'Keynote and workshop',
				'again' => 'Booked again: 2025 and 2026',
			],
			[
				'when'  => '2025-11',
				'room'  => 'MASFAA',
				'what'  => 'Keynote',
				'again' => '',
			],
			[
				'when'  => '2025-08',
				'room'  => 'AMA Central PA',
				'what'  => 'Keynote',
				'again' => '',
			],
			[
				'when'  => '2025-08',
				'room'  => 'JLL',
				'what'  => 'Two sessions',
				'again' => '',
			],
			[
				'when'  => '2025-07',
				'room'  => 'Okta',
				'what'  => 'Workshop',
				'again' => '',
			],
		];
	}

	/**
	 * Older stages for the "Also spoken at" block: label, and an optional source (label and URL) for a stage with one.
	 * Penn named these on 2026-10-26. Dreamforce 2012 and INBOUND 2016 carry the independent sources of 2026-10-25.
	 *
	 * @return array<int, array{html: string}>
	 */
	public static function also_spoken(): array {
		$link = static fn( string $text, string $url ): string => '<a class="bp-link" href="' . esc_url( $url ) . '" rel="noopener">' . esc_html( $text ) . '</a>';

		return [
			[ 'html' => 'IBM Think' ],
			[ 'html' => 'IBM World of Watson, 2016' ],
			[ 'html' => 'Dreamforce, ' . $link( '2012', 'https://spinsucks.com/communication/dreamforce-a-must-attend-event-for-marketers-and-pr-professionals/' ) ],
			[ 'html' => 'INBOUND, ' . $link( '2016', 'https://www.youtube.com/watch?v=U52b1O-059Y' ) . ' and 2024' ],
			[ 'html' => 'Social Media Marketing World' ],
			[ 'html' => 'Content Marketing World' ],
			[ 'html' => 'MarketingProfs B2B Forum' ],
			[ 'html' => 'MAICON, 2019 opening keynote and 2026' ],
		];
	}

	/**
	 * The reach line: newsletter and LinkedIn, each with the month of the count. Podcast, website, and
	 * YouTube figures stay off the site because their dates are unverified.
	 *
	 * @return string
	 */
	public static function reach(): string {
		$count = function_exists( 'crispytheme_get_subscriber_count' ) ? crispytheme_get_subscriber_count() : 'About 292K';
		$month = function_exists( 'crispytheme_get_subscriber_as_of' ) ? crispytheme_get_subscriber_as_of() : 'October 2026';

		return sprintf(
			'%1$s newsletter subscribers (%2$s). %3$s LinkedIn followers (%4$s).',
			$count,
			$month,
			number_format( BookingFacts::int( 'linkedin_followers' ) ),
			BookingFacts::get( 'linkedin_as_of' )
		);
	}

	/**
	 * Section: reach. The promotion sentence beside the action, then a photograph with two figure tiles:
	 * the newsletter and LinkedIn counts, each with its month and year.
	 *
	 * @param string $action_href Address of the action.
	 * @return string
	 */
	public static function reach_section( string $action_href ): string {
		$count = function_exists( 'crispytheme_get_subscriber_count' ) ? crispytheme_get_subscriber_count() : 'About 292K';
		$month = function_exists( 'crispytheme_get_subscriber_as_of' ) ? crispytheme_get_subscriber_as_of() : 'October 2026';

		return '<section id="reach" class="bp-sec reach bp-alt" aria-labelledby="bp-reach-h">'
			. '<div class="bp-in">'
			. '<div class="reach__head">'
			. '<h2 id="bp-reach-h" data-reveal>Promotion comes with the keynote</h2>'
			. '<div class="reach__say" data-reveal><p class="reach__promo">' . esc_html( self::PROMOTION ) . '</p>'
			. '<a class="bp-btn" href="' . esc_url( $action_href ) . '">Check my dates</a>'
			. '<p class="reach__reply">' . esc_html( self::REPLY ) . '</p></div>'
			. '</div></div>'
			. '<div class="reach__band"><div class="bp-in reach__mosaic">'
			. '<figure class="reach__pic" data-reveal>' . BookingPage::img( 'reach', 'Christopher Penn speaks with both hands raised in front of a dark screen.' ) . '</figure>'
			. '<div class="reach__figs">'
			. '<p class="reach__tile reach__tile--navy" data-reveal><span class="reach__fig">' . esc_html( $count ) . '</span><span class="reach__cap">newsletter subscribers (' . esc_html( $month ) . ')</span></p>'
			. '<p class="reach__tile reach__tile--tint" data-reveal><span class="reach__fig">' . esc_html( number_format( BookingFacts::int( 'linkedin_followers' ) ) ) . '</span><span class="reach__cap">LinkedIn followers (' . esc_html( BookingFacts::get( 'linkedin_as_of' ) ) . ')</span></p>'
			. '</div></div></div></section>';
	}

	/**
	 * Split a room into its name and its event: "MAICON, AI for Writers Summit" becomes two parts.
	 *
	 * @param string $room Room text.
	 * @return array{0: string, 1: string}
	 */
	private static function split_room( string $room ): array {
		$parts = explode( ', ', $room, 2 );

		return [ $parts[0], $parts[1] ?? '' ];
	}

	/**
	 * Section: recent rooms. The room is the big name, the month and year the second figure, and a repeat
	 * booking a filled mark. The reel sits beside the list, as a short silent loop with a control for the full reel.
	 *
	 * @param bool $first_person Write in the voice of the booking page.
	 * @return string
	 */
	public static function rooms_section( bool $first_person ): string {
		$rows  = '';
		$again = count( array_filter( array_column( self::rooms(), 'again' ) ) );
		$word  = [ 'No rooms', 'One of them', 'Two of them', 'Three of them' ][ min( $again, 3 ) ];

		foreach ( self::rooms() as $row ) {
			[ $name, $event ] = self::split_room( $row['room'] );

			$rows .= '<li class="room" data-reveal>'
				. '<div class="room__main"><h3 class="room__name">' . esc_html( $name ) . '</h3>'
				. ( '' !== $event ? '<p class="room__event"><span class="pk__vh">, </span>' . esc_html( $event ) . '</p>' : '' )
				. '<p class="room__what">' . esc_html( $row['what'] ) . '</p></div>'
				. '<p class="room__when"><time datetime="' . esc_attr( $row['when'] ) . '">' . esc_html( ProblemPicker::month_year( $row['when'] ) ) . '</time></p>'
				. ( '' !== $row['again'] ? '<p class="room__again">' . esc_html( $row['again'] ) . '</p>' : '' )
				. '</li>';
		}

		return '<section id="rooms" class="bp-sec rooms bp-alt" aria-labelledby="bp-rooms-h">'
			. '<div class="bp-in rooms__in">'
			. '<div class="rooms__side"><h2 id="bp-rooms-h" data-reveal>Recent rooms</h2>'
			. '<p class="rooms__lead" data-reveal>' . esc_html( $first_person ? "Rooms where I spoke, newest first. $word booked me again." : "Rooms where Christopher spoke, newest first. $word booked him again." ) . '</p>'
			. '<p class="rooms__act" data-reveal><a class="bp-link" href="' . esc_url( BookingFacts::form_url() ) . '">Check my dates</a></p>'
			. '</div>'
			. self::reel_frame()
			. '<ol class="rooms__list" role="list">' . $rows . '</ol>'
			. '</div></section>';
	}

	/**
	 * The reel frame: a silent loop cut from the reel, with a poster, and the control for the full reel.
	 * Without a loop file, it shows a photograph. Without a reel file, it shows no control.
	 *
	 * @return string
	 */
	private static function reel_frame(): string {
		$reel   = BookingPage::reel();
		$loop   = BookingFacts::get( 'reel_loop_url' );
		$poster = BookingFacts::get( 'reel_poster' );
		$media  = '' !== $loop
			? '<video class="rooms__loop" muted loop playsinline preload="metadata" aria-hidden="true"' . ( '' !== $poster ? ' poster="' . esc_url( $poster ) . '"' : '' ) . ' src="' . esc_url( $loop ) . '"></video>'
			: BookingPage::img( 'stages', 'Christopher Penn walks across a large stage in front of a lit screen.' );

		return '<figure class="rooms__reel" data-reveal>' . $media
			. ( '' !== $reel['button'] ? '<figcaption>' . $reel['button'] . '</figcaption>' : '' )
			. '</figure>';
	}

	/**
	 * Section: also spoken at, the credentials sentence, and the company names, each a designed list.
	 *
	 * @param bool $first_person Write in the voice of the booking page.
	 * @return string
	 */
	public static function also_section( bool $first_person ): string {
		$stages = '';
		$teams  = '';

		foreach ( self::also_spoken() as $row ) {
			$stages .= '<li class="also__stage">' . $row['html'] . '</li>';
		}

		foreach ( BookingPage::client_list() as $name ) {
			$teams .= '<li class="also__team">' . esc_html( $name ) . '</li>';
		}

		$cred = $first_person
			? sprintf( 'I am an %1$s-time IBM Champion, and %2$d was the final year. Author of %3$d published books, %4$d editions.', BookingFacts::get( 'ibm_champion_times' ), BookingFacts::int( 'ibm_final_year' ), BookingFacts::int( 'book_titles' ), BookingFacts::int( 'book_editions' ) )
			: sprintf( '%1$s-time IBM Champion, and %2$d was the final year. Author of %3$d published books, %4$d editions.', ucfirst( BookingFacts::get( 'ibm_champion_times' ) ), BookingFacts::int( 'ibm_final_year' ), BookingFacts::int( 'book_titles' ), BookingFacts::int( 'book_editions' ) );

		return '<section id="also" class="bp-sec also bp-alt" aria-labelledby="bp-also-h" data-also>'
			. '<div class="bp-in also__in">'
			. '<div class="also__side"><h2 id="bp-also-h" data-reveal>Also spoken at</h2>'
			. '<p class="also__note" data-reveal>' . esc_html( $first_person ? 'Older stages, listed apart from the recent rooms above.' : 'Older stages, listed apart from the recent rooms.' ) . '</p></div>'
			. '<div class="also__main">'
			. '<details class="bp-fold bp-fold--quiet" data-wide-open open><summary>The eight older stages</summary><ul class="also__list" role="list" data-reveal>' . $stages . '</ul></details>'
			. '<p class="also__cred" data-reveal>' . esc_html( $cred ) . '</p>'
			. '<details class="bp-fold also__fold" data-wide-open open data-reveal><summary>' . esc_html( $first_person ? 'Teams I have worked with' : 'Teams Christopher has worked with' ) . '</summary>'
			. '<ul class="also__teams" role="list">' . $teams . '</ul></details>'
			. '<p class="also__act" data-reveal><a class="bp-link" href="' . esc_url( BookingFacts::form_url() ) . '">Check my dates</a></p>'
			. '</div></div></section>';
	}

	/**
	 * Section: the LPA letter beside its repeat booking. The letter dates from April 2024, so it never
	 * appears without the line that LPA booked again in September 2026.
	 *
	 * @param bool $full         Show the whole letter, or its first paragraph with a link to the rest.
	 * @param bool $first_person Write in the voice of the booking page.
	 * @return string
	 */
	public static function letter_section( bool $full, bool $first_person ): string {
		$paragraphs = BookingPage::letter_paragraphs();
		$shown      = $full ? $paragraphs : [ $paragraphs[0] ];
		$body       = '';

		foreach ( $shown as $paragraph ) {
			$body .= '<p>' . esc_html( $paragraph ) . '</p>';
		}

		$more = $full ? '' : '<a class="bp-link letter__more" href="' . esc_url( BookingFacts::get( 'page_path' ) . '#letter' ) . '">Read the whole letter</a>';

		return '<section id="letter" class="bp-sec letter" aria-labelledby="bp-letter-h">'
			. '<div class="bp-in letter__in">'
			. '<figure class="letter__pic" data-reveal>' . BookingPage::img( 'level', 'Christopher Penn speaks with both hands open next to a lectern.' ) . '</figure>'
			. '<div class="letter__main"><h2 id="bp-letter-h" data-reveal>What LPA wrote</h2>'
			. '<p class="letter__again" data-reveal>' . esc_html( $first_person ? 'LPA booked me again in September 2026.' : 'LPA booked Christopher Penn again in September 2026.' ) . '</p>'
			. '<figure class="letter__body" data-reveal><blockquote>' . $body . '</blockquote>'
			. '<figcaption><span class="letter__name">Clark Mulligan</span><span>President, LPA, ' . esc_html( BookingFacts::get( 'quote_date' ) ) . '</span>' . $more . '</figcaption></figure>'
			. '</div></div></section>';
	}
}
