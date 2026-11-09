<?php
/**
 * Audience problem picker.
 *
 * The front door of the home page and the first field of the booking form (Penn, 2026-10-26): a planner
 * names the audience's main problem and sees the talk that answers it and, when a file backs it, a
 * recent room. The six problems live here once. The home page, the booking page, the form tool, and the
 * script all read this list.
 *
 * @package CrispyTheme
 * @since 2.2.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Audience problem picker class.
 */
final class ProblemPicker {

	/**
	 * The question. It is the first thing on the home page and the first field of the form.
	 */
	public const QUESTION = 'What is your audience’s main problem?';

	/**
	 * Form field label. Gravity Forms stores plain text, so the apostrophe is straight.
	 */
	public const FIELD_LABEL = "What is your audience's main problem?";

	/**
	 * Query parameter that fills the first form field.
	 */
	public const PARAM = 'problem';

	/**
	 * Choice for a planner whose problem is not in the list.
	 */
	public const OTHER = 'Something else, or not sure yet';

	/**
	 * Month names for the room dates.
	 *
	 * @var string[]
	 */
	private const MONTHS = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];

	/**
	 * Write a year-month as text, for example 2026-04 becomes April 2026.
	 *
	 * @param string $ym Year and month, YYYY-MM.
	 * @return string
	 */
	public static function month_year( string $ym ): string {
		[ $year, $month ] = array_map( 'intval', explode( '-', $ym ) + [ 0, 0 ] );

		return ( $month >= 1 && $month <= 12 ) ? self::MONTHS[ $month - 1 ] . ' ' . $year : $ym;
	}

	/**
	 * The six problems. Sources: docs/client-context.md (Penn, 2026-10-26), the problem list, the talks,
	 * and the rooms. A room shows only when a file backs it. A missing talk title or room stays empty.
	 *
	 * Keys: slug, label (the category), phrase (the planner's words), promise, talk (approved title or empty),
	 * topic (text for the form topic field), room (event name or empty), when (YYYY-MM or empty).
	 *
	 * @return array<int, array{slug: string, label: string, phrase: string, promise: string, talk: string, topic: string, room: string, when: string}>
	 */
	public static function problems(): array {
		return [
			[
				'slug'    => 'enablement',
				'label'   => 'AI enablement and adoption',
				'phrase'  => 'our people do not use it well',
				'promise' => 'Name the level your people work at today, and the next step up.',
				'talk'    => BookingPage::LEAD_TALK,
				'topic'   => BookingPage::LEAD_TALK,
				'room'    => 'Self Storage Institute',
				'when'    => '2026-04',
			],
			[
				'slug'    => 'visibility',
				'label'   => 'AI visibility',
				'phrase'  => 'people no longer find us',
				'promise' => 'AI reads text, not design.',
				'talk'    => '',
				'topic'   => 'AI visibility',
				'room'    => 'Spotlight Summit',
				'when'    => '2026-09',
			],
			[
				'slug'    => 'judgment',
				'label'   => 'AI skills and judgment',
				'phrase'  => 'our people stop thinking',
				'promise' => 'Keep every decision with your people while AI does the work.',
				'talk'    => BookingPage::ALTERNATE_TALKS[0],
				'topic'   => BookingPage::ALTERNATE_TALKS[0],
				'room'    => 'Alberta Tourism Advocacy Summit',
				'when'    => '2026-02',
			],
			[
				'slug'    => 'daily-work',
				'label'   => 'AI in daily work',
				'phrase'  => 'where does it save time',
				'promise' => 'Use AI as a partner in daily work, and never think alone.',
				'talk'    => 'Never Think Alone',
				'topic'   => 'Never Think Alone',
				'room'    => 'JLL and AMA Central PA',
				'when'    => '2025-08',
			],
			[
				'slug'    => 'governance',
				'label'   => 'AI governance and data privacy',
				'phrase'  => 'we fear leaks and lock-in',
				'promise' => 'Learn why every business needs a local AI fallback.',
				'talk'    => BookingPage::ALTERNATE_TALKS[1],
				'topic'   => BookingPage::ALTERNATE_TALKS[1],
				'room'    => '',
				'when'    => '',
			],
			[
				'slug'    => 'analytics',
				'label'   => 'AI analytics',
				'phrase'  => 'we cannot trust the numbers',
				'promise' => 'Do data analysis with the AI tools you already have, with no coding.',
				'talk'    => 'Build an Analytics Powerhouse with AI',
				'topic'   => 'Build an Analytics Powerhouse with AI',
				'room'    => 'MAICON',
				'when'    => '2026-10',
			],
		];
	}

	/**
	 * The text of a problem as the planner reads it, and as the form choice stores it.
	 *
	 * @param array{label: string, phrase: string} $problem One row of problems().
	 * @return string
	 */
	public static function text( array $problem ): string {
		return $problem['label'] . ': ' . $problem['phrase'];
	}

	/**
	 * The values of the first form field, in order, with the last choice for a problem that is not listed.
	 *
	 * @return string[]
	 */
	public static function choices(): array {
		return array_merge( array_map( [ self::class, 'text' ], self::problems() ), [ self::OTHER ] );
	}

	/**
	 * Build the link that opens the booking form with the problem and the topic filled in.
	 *
	 * @param string $problem Form choice text.
	 * @param string $topic   Topic text.
	 * @return string
	 */
	public static function link( string $problem, string $topic ): string {
		$query = [ self::PARAM => $problem ];

		if ( '' !== $topic ) {
			$query['topic'] = $topic;
		}

		return BookingFacts::get( 'page_path' ) . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) . '#check-my-dates';
	}

	/**
	 * The room of a problem as text, or an empty string when no file backs a room.
	 *
	 * @param array{room: string, when: string} $problem One row of problems().
	 * @return string
	 */
	public static function room( array $problem ): string {
		return '' === $problem['room'] || '' === $problem['when'] ? '' : $problem['room'] . ', ' . self::month_year( $problem['when'] );
	}

	/**
	 * Find the problem that matches a request value, or null.
	 *
	 * @param string $value Form choice text, as the radio sends it.
	 * @return array{slug: string, label: string, phrase: string, promise: string, talk: string, topic: string, room: string, when: string}|null
	 */
	public static function find( string $value ): ?array {
		foreach ( self::problems() as $problem ) {
			if ( self::text( $problem ) === $value ) {
				return $problem;
			}
		}

		return null;
	}

	/**
	 * Fill the topic field of the form from the problem in the address. A radio group sends the problem only,
	 * so a visitor without script still gets the topic. A topic in the address always wins.
	 *
	 * @param mixed $value Value that Gravity Forms found for the field.
	 * @return mixed
	 */
	public static function topic_from_request( mixed $value ): mixed {
		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}

		$problem = isset( $_GET[ self::PARAM ] ) ? self::find( sanitize_text_field( wp_unslash( (string) $_GET[ self::PARAM ] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return null === $problem ? $value : $problem['topic'];
	}

	/**
	 * The answer to one problem: the promise, the talk, and a recent room when a file backs one.
	 *
	 * @param array{slug: string, promise: string, talk: string, room: string, when: string} $problem One row of problems().
	 * @return string
	 */
	private static function answer( array $problem ): string {
		$room = self::room( $problem );

		return '<div class="pk__answer" id="pk-a-' . esc_attr( $problem['slug'] ) . '">'
			. '<p class="pk__promise">' . esc_html( $problem['promise'] ) . '</p>'
			. ( '' !== $problem['talk'] ? '<p class="pk__row"><span class="pk__k">Talk</span><span class="pk__talk">' . esc_html( $problem['talk'] ) . '</span></p>' : '' )
			. ( '' !== $room ? '<p class="pk__row"><span class="pk__k">Room</span><span class="pk__room">' . esc_html( $room ) . '</span></p>' : '' )
			. '</div>';
	}

	/**
	 * The menu: a radio group in a form. Each choice is a label with a large name. The chosen choice shows its
	 * answer. Without script, a plain GET submit opens the booking form with the problem filled in. With script,
	 * a choice fills the form on the booking page. The first choice is checked, so an answer shows at once.
	 *
	 * @param string $actions    Markup of the buttons and links after the list (trusted, built in this theme).
	 * @param string $after_list Markup of one quiet line under the list, for a planner whose problem is not listed.
	 * @return string
	 */
	public static function menu( string $actions, string $after_list = '' ): string {
		$items = '';

		foreach ( self::problems() as $i => $problem ) {
			$text   = self::text( $problem );
			$id     = 'pk-' . $problem['slug'];
			$items .= '<li class="pk__item">'
				. '<input class="pk__radio" type="radio" name="' . esc_attr( self::PARAM ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $text ) . '" data-topic="' . esc_attr( $problem['topic'] ) . '"'
				. ( 0 === $i ? ' checked' : '' ) . ' aria-describedby="pk-a-' . esc_attr( $problem['slug'] ) . '">'
				. '<label class="pk__label" for="' . esc_attr( $id ) . '"><span class="pk__name">' . esc_html( $problem['label'] ) . '</span>'
				. '<span class="pk__vh">: </span><span class="pk__phrase">' . esc_html( $problem['phrase'] ) . '</span></label>'
				. self::answer( $problem )
				. '</li>';
		}

		return '<form class="pk" method="get" action="' . esc_url( BookingFacts::get( 'page_path' ) . '#check-my-dates' ) . '" data-bp-picker>'
			. '<fieldset class="pk__set"><legend class="pk__legend"><h2 id="pk-q">' . esc_html( self::QUESTION ) . '</h2></legend>'
			. '<ol class="pk__list" role="list">' . $items . '</ol>'
			. ( '' !== $after_list ? '<p class="pk__other">' . $after_list . '</p>' : '' )
			. '</fieldset>'
			. '<div class="pk__act">' . $actions . '</div>'
			. '</form>';
	}

	/**
	 * Build the plain link for a planner whose problem is not listed.
	 *
	 * @return string
	 */
	public static function other_link(): string {
		return '<a class="bp-link" href="' . esc_url( self::link( self::OTHER, '' ) ) . '" data-problem="' . esc_attr( self::OTHER ) . '" data-topic="">My problem is not here. Go to the form.</a>';
	}
}
