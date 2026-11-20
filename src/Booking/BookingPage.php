<?php
/**
 * Booking page renderer.
 *
 * Builds the markup of the /public-speaking/ booking page from the copy deck of 2026-10-25 and the
 * facts in BookingFacts. Every fee, term, count, and date comes from a Theme Option, so a change of
 * option changes every place that states the fact.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Booking page renderer class.
 */
final class BookingPage {

	/**
	 * Credit line for every photograph.
	 */
	public const PHOTO_CREDIT = 'Photo: Christopher S. Penn';

	/**
	 * Name of the lead keynote.
	 */
	public const LEAD_TALK = 'The Five Levels of AI Enablement';

	/**
	 * The alternate keynote (docs/client-context.md: lead keynote and alternate). Penn does not speak on AI governance (2026-11-13).
	 *
	 * @var string[]
	 */
	public const ALTERNATE_TALKS = [ 'Nobody Decided This' ];

	/**
	 * Cached photo manifest.
	 *
	 * @var array<string, array{file: string, width: int, height: int}>|null
	 */
	private static ?array $manifest = null;

	/**
	 * Tell if the current request shows the booking page.
	 *
	 * @return bool
	 */
	public static function is_booking_page(): bool {
		return function_exists( 'is_page' ) && is_page( trim( BookingFacts::get( 'page_path' ), '/' ) );
	}

	/**
	 * Name of the dynamic block that prints the page.
	 */
	public const BLOCK = 'crispytheme/booking-page';

	/**
	 * Register the dynamic block. The pattern and the template use it, so the options are read only on this page.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the block type.
	 *
	 * @return void
	 */
	public function register_block(): void {
		self::register_dynamic_block( self::BLOCK, [ self::class, 'render_block' ] );
	}

	/**
	 * Register a dynamic block type. The home page uses it too.
	 *
	 * @param string   $name   Block name.
	 * @param callable $render Render callback.
	 * @return void
	 */
	public static function register_dynamic_block( string $name, callable $render ): void {
		register_block_type(
			$name,
			[
				'api_version'     => '3',
				'render_callback' => $render,
			]
		);
	}

	/**
	 * Render the block. The form shortcode inside the markup runs here, because WordPress does not run shortcodes in a template.
	 *
	 * @return string
	 */
	public static function render_block(): string {
		return do_shortcode( self::html() );
	}

	/**
	 * Get the block markup of the pattern.
	 *
	 * @return string
	 */
	public static function content(): string {
		return '<!-- wp:' . self::BLOCK . ' /-->';
	}

	/**
	 * Get the HTML of the whole page: the main element and the quiet footer line.
	 *
	 * @return string
	 */
	public static function html(): string {
		$reel     = self::reel();
		$sections = [
			self::hero(),
			Proof::reach_section( '#check-my-dates' ),
			Proof::rooms_section( true ),
			Proof::letter_section( true, true ),
			Proof::also_section( true ),
			self::levels_section( true ),
			self::fees(),
			self::steps(),
			self::faq(),
			self::form(),
		];

		return '<main id="main" class="bp bp-book" data-facts-as-of="' . esc_attr( BookingFacts::get( 'facts_as_of' ) ) . '">' . "\n"
			. implode( "\n", $sections ) . "\n" . $reel['dialog'] . "\n</main>\n"
			. self::footer();
	}

	/**
	 * Read the photo manifest written by src/booking_assets.py.
	 *
	 * @return array<string, array{file: string, width: int, height: int}>
	 */
	public static function manifest(): array {
		if ( null !== self::$manifest ) {
			return self::$manifest;
		}

		$path = CRISPY_THEME_DIR . '/assets/images/booking/manifest.json';
		$data = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		self::$manifest = is_array( $data ) ? $data : [];

		return self::$manifest;
	}

	/**
	 * Build the URL of a photo.
	 *
	 * @param string $slot Slot name from the manifest.
	 * @return string
	 */
	public static function photo_url( string $slot ): string {
		$meta = self::manifest()[ $slot ] ?? null;

		return null === $meta ? '' : CRISPY_THEME_URI . '/assets/images/booking/' . $meta['file'];
	}

	/**
	 * Build an img tag for a photo slot.
	 *
	 * @param string $slot  Slot name.
	 * @param string $alt   Alternative text.
	 * @param string $extra Extra attributes.
	 * @return string
	 */
	public static function img( string $slot, string $alt, string $extra = 'loading="lazy"' ): string {
		$meta = self::manifest()[ $slot ] ?? null;

		if ( null === $meta ) {
			return '';
		}

		return sprintf(
			'<img src="%1$s" width="%2$d" height="%3$d" alt="%4$s" decoding="async" %5$s>',
			esc_url( self::photo_url( $slot ) ),
			(int) $meta['width'],
			(int) $meta['height'],
			esc_attr( $alt ),
			$extra
		);
	}

	/**
	 * Build a link to the form with the topic filled in. A level belongs to the first problem, AI enablement and adoption.
	 *
	 * @param string $topic Topic text.
	 * @return string
	 */
	public static function topic_link( string $topic ): string {
		return ProblemPicker::link( ProblemPicker::text( ProblemPicker::problems()[0] ), $topic );
	}

	/**
	 * Get the five levels with their lines and form topics.
	 *
	 * @return array<int, array{name: string, level: string, line: string, share: string, bar: int, topic: string}>
	 */
	public static function levels(): array {
		// Name, line, and the share of the work the audience does (the canonical slide, Penn, 2026-10-25): label, then bar width in percent.
		$rows = [
			[ 'Done by you', 'Workflow. You’re doing all the work.', '100%', 100 ],
			[ 'Done with you', 'Automation. You’re doing some of the work.', '60 to 70%', 70 ],
			[ 'Done for you', 'Agent. You’re doing very little of the work.', '10 to 20%', 20 ],
			[ 'Done without you', 'Autonomous. You’re doing almost none of the work.', '1 to 5%', 5 ],
			[ 'Done ahead of you', 'Anticipatory. AI identifies needs before you do.', '0%', 0 ],
		];
		$out  = [];

		foreach ( $rows as $i => $row ) {
			$n     = $i + 1;
			$out[] = [
				'name'  => $row[0],
				'level' => 'Level ' . $n,
				'line'  => $row[1],
				'share' => $row[2],
				'bar'   => $row[3],
				'topic' => sprintf( '%1$s, audience at Level %2$d (%3$s)', self::LEAD_TALK, $n, $row[0] ),
			];
		}

		return $out;
	}

	/**
	 * Write a refund percent as text.
	 *
	 * @param string $key Fact key.
	 * @return string
	 */
	private static function refund( string $key ): string {
		return 0 === BookingFacts::int( $key ) ? 'none' : BookingFacts::percent( $key );
	}

	/**
	 * Write the reel dialog and its control.
	 *
	 * @return array{button: string, dialog: string}
	 */
	public static function reel(): array {
		$url = BookingFacts::get( 'reel_url' );

		if ( '' === $url ) {
			return [
				'button' => '',
				'dialog' => '',
			];
		}

		$poster = BookingFacts::get( 'reel_poster' );
		$id     = self::youtube_id( $url );
		// A YouTube reel loads only when the dialog opens (the script sets src from data-src), so the page loads no YouTube code.
		$media = '' !== $id
			? '<iframe title="Demo reel" data-src="' . esc_url( self::youtube_embed( $id ) . '?rel=0&modestbranding=1&autoplay=1' ) . '" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>'
			: '<video controls preload="none" playsinline'
				. ( '' !== $poster ? ' poster="' . esc_url( $poster ) . '"' : '' )
				. ' src="' . esc_url( $url ) . '"></video>';

		return [
			'button' => '<button type="button" class="bp-link bp-reel-open" data-reel-open>Play the reel</button>',
			'dialog' => '<dialog class="bp-reel" aria-label="Demo reel">' . $media . '<button type="button" class="bp-btn" data-reel-close>Close</button></dialog>',
		];
	}

	/**
	 * Get the video id of a YouTube watch, short, or embed link.
	 *
	 * @param string $url Reel URL.
	 * @return string The 11-character id, or an empty string for any other URL.
	 */
	public static function youtube_id( string $url ): string {
		return 1 === preg_match( '#^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^\s]*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])#', $url, $m ) ? $m[1] : '';
	}

	/**
	 * Get the privacy-friendly embed address of a YouTube video.
	 *
	 * @param string $id Video id.
	 * @return string
	 */
	public static function youtube_embed( string $id ): string {
		return 'https://www.youtube-nocookie.com/embed/' . $id;
	}

	/**
	 * Section 1: first screen. The menu card of the audience's main problem beside the MAICON photograph.
	 *
	 * @return string
	 */
	private static function hero(): string {
		return Hero::render(
			'Book Christopher Penn<br>to keynote your event',
			'AI builder, keynote speaker. I build what I speak about.',
			'#fees',
			true
		);
	}

	/**
	 * Section: the talk behind the first problem, The Five Levels of AI Enablement. It sits below the proof.
	 * The rows are links that fill the booking form with the level. On the booking page a script fills the form
	 * in place. On the home page the links open the booking page.
	 *
	 * @param bool $on_form_page True on the booking page, which holds the form.
	 * @return string
	 */
	public static function levels_section( bool $on_form_page ): string {
		$rows = '';
		$prob = ProblemPicker::text( ProblemPicker::problems()[0] );

		foreach ( self::levels() as $level ) {
			$rows .= '<li data-reveal><a class="lv" href="' . esc_url( self::topic_link( $level['topic'] ) ) . '" data-topic="' . esc_attr( $level['topic'] ) . '" data-problem="' . esc_attr( $prob ) . '" style="--bar:' . (int) $level['bar'] . '">'
				. '<span class="lv__lvl">' . esc_html( $level['level'] ) . '</span>'
				. '<span class="lv__name">' . esc_html( $level['name'] ) . '</span>'
				. '<span class="lv__line">' . esc_html( $level['line'] ) . '</span>'
				. '<span class="lv__share"><span class="lv__label">You do ' . esc_html( $level['share'] ) . ' of the work</span><span class="lv__bar" aria-hidden="true"></span></span>'
				. '</a></li>';
		}

		$skip   = self::topic_link( self::LEAD_TALK . ', level not chosen' );
		$lead   = $on_form_page
			? 'My lead keynote, and the talk behind the first problem above. It names who does the work at each level.'
			: 'The talk behind the first problem above, AI enablement and adoption. It names who does the work at each level.';
		$pick   = $on_form_page
			? 'Pick the level your audience works at today, and the form starts with it.'
			: 'Pick the level your audience works at today, and the booking form starts with it.';
		$result = $on_form_page
			? '<div class="levels__result" data-finder-result hidden><p aria-live="polite">Your form will say: <span data-finder-topic></span></p><a class="bp-btn" href="#check-my-dates">Check my dates</a></div>'
			: '';

		return '<section id="levels" class="bp-sec levels" aria-labelledby="bp-levels-h" data-bp-finder>'
			. '<div class="bp-in levels__in">'
			. '<div class="levels__head">'
			. '<h2 id="bp-levels-h" data-reveal>' . esc_html( self::LEAD_TALK ) . '</h2>'
			. '<p data-reveal>' . esc_html( $lead ) . '</p>'
			. '<p data-reveal>Each level builds on the one before it. An organization cannot skip a level. ' . esc_html( $pick ) . '</p>'
			. '</div>'
			. '<div class="levels__main"><details class="bp-fold bp-fold--quiet" data-wide-open open><summary>The five levels</summary><ol class="levels__list" role="list">' . $rows . '</ol></details>'
			. $result
			. '<p class="levels__skip"><a class="bp-link" href="' . esc_url( $skip ) . '" data-topic="' . esc_attr( self::LEAD_TALK . ', level not chosen' ) . '" data-problem="' . esc_attr( $prob ) . '">Not sure yet? Go to the form.</a></p>'
			. '</div></div></section>';
	}

	/**
	 * The paragraphs of the LPA letter, word for word (Clark Mulligan, President, LPA). The date comes from the quote_date fact.
	 *
	 * @return string[]
	 */
	public static function letter_paragraphs(): array {
		return [
			'Not only were you the highest rated presenter we have ever had for a sales and marketing workshop, but based on our survey feedback as of right now, you are looking to be the highest rated presenter we have ever had for a spring meeting.',
			'And rightfully so. The content you delivered to our members was absolutely spot on. A number of attendees commented to me as to how timely and relevant your presentations were, especially the hands-on component.',
			'It made many realize just how behind they are, but you gave them inspiration and ideas as to how they can incorporate AI into their companies moving forward.',
		];
	}

	/**
	 * The company names as an array, with the curly apostrophe.
	 *
	 * @return string[]
	 */
	public static function client_list(): array {
		$names = array_filter( array_map( 'trim', explode( ',', BookingFacts::get( 'client_names' ) ) ) );

		return array_values( array_map( static fn( string $name ): string => str_replace( "'", '’', $name ), $names ) );
	}

	/**
	 * Build one menu row.
	 *
	 * @param string $name  Row name.
	 * @param string $value Row value.
	 * @param string $line  Line under the name.
	 * @return string
	 */
	private static function row( string $name, string $value, string $line = '' ): string {
		return '<li class="bp-menu__row"><span class="bp-menu__name">' . esc_html( $name ) . '</span><span class="bp-menu__value">' . esc_html( $value ) . '</span>'
			. ( '' !== $line ? '<span class="bp-menu__line">' . esc_html( $line ) . '</span>' : '' ) . '</li>';
	}

	/**
	 * Build one term as a figure: the name, the value set large, and the line under it.
	 *
	 * @param string $name  Term name.
	 * @param string $value Term value.
	 * @param string $line  Line under the value.
	 * @return string
	 */
	private static function fig( string $name, string $value, string $line = '' ): string {
		return '<li class="bp-fig"><span class="bp-fig__name">' . esc_html( $name ) . '</span><span class="bp-fig__value">' . esc_html( $value ) . '</span>'
			. ( '' !== $line ? '<span class="bp-fig__line">' . esc_html( $line ) . '</span>' : '' ) . '</li>';
	}

	/**
	 * Build one menu group.
	 *
	 * @param string $title Group name.
	 * @param string $rows  Row markup.
	 * @param string $intro Optional line under the group name.
	 * @param bool   $fold  Fold the group behind its name on a phone.
	 * @return string
	 */
	private static function group( string $title, string $rows, string $intro = '', bool $fold = false ): string {
		$list  = '<ul class="bp-menu__rows" role="list">' . $rows . '</ul>';
		$intro = '' !== $intro ? '<p class="bp-menu__intro">' . esc_html( $intro ) . '</p>' : '';

		if ( $fold ) {
			// Folds on a phone, open on a wide screen (script), readable without script.
			return '<div class="bp-menu__group" data-reveal><details class="bp-fold" data-wide-open open><summary>' . esc_html( $title ) . '</summary>' . $intro . $list . '</details></div>';
		}

		return '<div class="bp-menu__group" data-reveal><h3>' . esc_html( $title ) . '</h3>' . $intro . $list . '</div>';
	}

	/**
	 * Section 5: fees and terms.
	 *
	 * @return string
	 */
	private static function fees(): string {
		$reimburse = BookingFacts::int( 'reimburse_days' );

		$keynotes   = self::row( 'Keynotes', 'from ' . BookingFacts::money( 'fee_keynote_from' ), 'The fee covers the talk. Travel is a separate line.' );
		$workshops  = self::row( 'Workshops', 'from ' . BookingFacts::money( 'fee_workshop_from' ), 'Length and scope set the final fee.' );
		$travel     = self::row( 'Transportation, lodging, and meals', 'You pay', sprintf( 'Pre-pay them, or reimburse my team on receipts within %d days of the event.', $reimburse ) )
			. self::row( 'Flights', 'Business class where offered', 'If a route has no business class, the highest class offered.' )
			. self::row( 'International flights', 'Lie-flat business class', 'The contract names the seat.' );
		$terms_rows = self::fig( 'Deposit', BookingFacts::percent( 'deposit_percent' ), 'Due at signature. It follows the cancellation schedule, minus booked travel.' )
			. self::fig(
				'Cancellation',
				'By days before the event',
				sprintf(
					'More than 6 months: %1$s refund. 3 to 6 months: %2$s. Under 3 months: %3$s. Booked travel is never refunded.',
					self::refund( 'refund_over_6_months' ),
					self::refund( 'refund_3_to_6_months' ),
					self::refund( 'refund_under_3_months' )
				)
			)
			. self::fig( 'Balance', sprintf( '%d days before the event', BookingFacts::int( 'balance_days' ) ), 'The balance is due on that date.' )
			. self::fig( 'Travel term', 'In writing', 'In the contract before I hold a date.' );
		$includes   = self::row( 'Prep call', sprintf( '%d minutes', BookingFacts::int( 'prep_call_minutes' ) ), 'After signing.' )
			. self::row( 'Event promotion', 'Full-fee keynote', 'Newsletter inclusion, social media posts, and a promotional video clip. This covers a full-fee keynote only.' )
			. self::row( 'Transcript eBook', 'Keynote or workshop', 'Made from the transcript of your session.' )
			. self::row( 'Trust Insights inclusions', 'Six', 'Transcripts, an interactive AI instance, slide decks, session recordings, pre-event customization, and custom data for workshops.' );

		$fees  = '<section id="fees" class="bp-sec bp-fees bp-alt" aria-labelledby="bp-fees-h">'
			. '<div class="bp-in bp-fees__in">'
			. '<div class="bp-fees__side"><h2 id="bp-fees-h" data-reveal>Fees and terms</h2>'
			. '</div>'
			. '<div class="bp-menu">'
			. self::group( 'Keynote fees', $keynotes )
			. self::group( 'Workshop fees', $workshops )
			. self::group( 'Travel', $travel, '', true )
			. '</div>'
			. '<p class="bp-fees__act" data-reveal><a class="bp-link" href="#check-my-dates">Check my dates</a></p>'
			. '</div></section>';
		$terms = '<section id="terms" class="bp-sec bp-terms" aria-labelledby="bp-terms-h">'
			. '<div class="bp-in bp-terms__in">'
			. '<h2 id="bp-terms-h" data-reveal>Terms</h2>'
			. '<details class="bp-fold bp-fold--quiet" data-wide-open open><summary>Deposit, cancellation, balance, and travel term</summary><ul class="bp-figs" role="list" data-reveal>' . $terms_rows . '</ul></details>'
			. '<div class="bp-terms__incl">' . self::group( 'A full-fee booking includes', $includes, 'Full fee means the stated fee, with no discount and no negotiation.', true ) . '</div>'
			. '<p class="bp-terms__act" data-reveal><a class="bp-link" href="#check-my-dates">Check my dates</a></p>'
			. '</div></section>';

		return $fees . $terms;
	}

	/**
	 * Section 6: how booking works.
	 *
	 * @return string
	 */
	private static function steps(): string {
		$balance = BookingFacts::int( 'balance_days' );
		$prep    = BookingFacts::int( 'prep_call_minutes' );
		$fit     = BookingFacts::int( 'fit_call_minutes' );
		$steps   = [
			[ 'Today', 'Send the form with your event, date, topic, and budget band.' ],
			[ 'Within one business day', 'Trust Insights staff answer with the next step.' ],
			[ 'In the next few days', sprintf( 'A %d-minute fit call with Trust Insights business development. I join when you share a budget before the call.', $fit ) ],
			[ 'At signature', 'The contract carries the travel term, and you pay the deposit.' ],
			[ 'After signing', sprintf( 'A %d-minute prep call is included.', $prep ) ],
			[ sprintf( '%d days before the event', $balance ), 'The balance is due.' ],
			[ 'After the deposit', 'My team books travel once the deposit arrives.' ],
			[ 'Event day', 'I speak at your event.' ],
			[ sprintf( 'Within %d days of the event', BookingFacts::int( 'reimburse_days' ) ), 'You settle the travel receipts, unless you pre-paid them.' ],
		];
		$items   = '';

		foreach ( $steps as $step ) {
			$items .= '<li><strong>' . esc_html( $step[0] ) . '</strong><span>' . esc_html( $step[1] ) . '</span></li>';
		}

		return '<section class="bp-sec bp-steps bp-alt" aria-labelledby="bp-steps-h">'
			. '<div class="bp-in bp-steps__in">'
			. '<div class="bp-steps__head"><h2 id="bp-steps-h" data-reveal>How booking works</h2>'
			. '<p data-reveal>Each step has a time, so your team can plan around it.</p>'
			. '<figure class="bp-steps__pic" data-reveal>' . self::img( 'steps', 'Christopher Penn in profile, speaking to a room.' ) . '</figure></div>'
			. '<ol class="bp-slip" role="list" data-reveal>' . $items . '</ol>'
			. '</div></section>';
	}

	/**
	 * The planner questions and answers as plain text. The page and the JSON-LD graph both read this list,
	 * so the visible text and the schema cannot drift apart.
	 *
	 * @return array<int, array{0: string, 1: string}> Question and answer pairs.
	 */
	public static function faq_items(): array {
		return [
			[
				'What do you charge?',
				sprintf(
					'Keynotes from %1$s. Workshops from %2$s. Travel is billed at cost pursuant to contract requirements.',
					BookingFacts::money( 'fee_keynote_from' ),
					BookingFacts::money( 'fee_workshop_from' )
				),
			],
			[
				'Who pays for travel?',
				sprintf( 'The client pays all of it, either by pre-paying or by reimbursing my team after the trip. Payment is due within %d days of the event, on receipts. The contract states this before I hold a date.', BookingFacts::int( 'reimburse_days' ) ),
			],
			[
				'What does travel cover?',
				'Travel covers transportation, lodging, and meals. Flights are business class where the route offers it, or the highest class offered if it does not. International flights are lie-flat business class. The contract names that seat in writing.',
			],
			[
				'How big is the deposit, and can I get it back?',
				sprintf( 'The deposit is %d percent of the fee. It is due when you sign. My team books no travel before it arrives. If you cancel, the refund follows the schedule above, minus any travel my team has booked. Booked travel is never refunded.', BookingFacts::int( 'deposit_percent' ) ),
			],
			[
				'How fast will you reply?',
				'A member of my Trust Insights team answers every form within one business day, Eastern Time.',
			],
			[
				'Which talk should I book?',
				sprintf(
					'Start with your audience’s main problem in the form, and I match a talk to it. %1$s is my lead keynote. My alternate is %2$s.',
					self::LEAD_TALK,
					self::ALTERNATE_TALKS[0]
				),
			],
			[
				'Will you help fill seats?',
				sprintf(
					'%1$s My newsletter has %2$s subscribers (%3$s).',
					Proof::PROMOTION,
					lcfirst( function_exists( 'crispytheme_get_subscriber_count' ) ? crispytheme_get_subscriber_count() : 'About 292K' ),
					function_exists( 'crispytheme_get_subscriber_as_of' ) ? crispytheme_get_subscriber_as_of() : 'October 2026'
				),
			],
		];
	}

	/**
	 * Section 7: planner questions.
	 *
	 * @return string
	 */
	private static function faq(): string {
		$html = '';

		// The first answer stays open text. The rest fold on a phone and open on a wide screen (script), and stay readable without script.
		foreach ( self::faq_items() as $i => $item ) {
			$html .= $i < 1
				? '<p><strong>' . esc_html( $item[0] ) . '</strong> ' . esc_html( $item[1] ) . '</p>'
				: '<details data-wide-open open><summary>' . esc_html( $item[0] ) . '</summary><p>' . esc_html( $item[1] ) . '</p></details>';
		}

		return '<section class="bp-sec bp-faq" aria-labelledby="bp-faq-h">'
			. '<div class="bp-in bp-faq__in"><h2 id="bp-faq-h">Planner questions</h2>'
			. '<div class="bp-faq__text">' . $html . '</div></div></section>';
	}

	/**
	 * Section 8: the form on a coupon.
	 *
	 * @return string
	 */
	private static function form(): string {
		return '<section id="check-my-dates" class="bp-sec bp-form bp-alt" aria-labelledby="bp-form-h">'
			. '<div class="bp-in"><div class="bp-coupon">'
			. '<div class="bp-coupon__lead"><div class="bp-coupon__stick"><h2 id="bp-form-h">Check my dates</h2>'
			. '<p>The form goes to my team at Trust Insights, and they answer within one business day, Eastern Time.</p></div></div>'
			. '<div class="bp-coupon__fields">[gravityforms id="' . BookingFacts::int( 'form_id' ) . '" title="false" description="false" ajax="true"]</div>'
			. '</div></div></section>';
	}

	/**
	 * Section 9: the footer. A photograph on the left half. On the right half the menu of the site, and on the home page the newsletter.
	 *
	 * @param bool $newsletter Show the newsletter block (the home page).
	 * @return string
	 */
	public static function footer( bool $newsletter = false ): string {
		$year = function_exists( 'wp_date' ) ? (string) wp_date( 'Y' ) : gmdate( 'Y' );
		$news = '';

		if ( $newsletter ) {
			$count = function_exists( 'crispytheme_get_subscriber_count' ) ? crispytheme_get_subscriber_count() : 'About 292K';
			$month = function_exists( 'crispytheme_get_subscriber_as_of' ) ? crispytheme_get_subscriber_as_of() : 'October 2026';
			$news  = '<div class="foot__news"><h2>Almost Timely newsletter</h2>'
				. '<p>Weekly AI and marketing insights from Christopher Penn.</p>'
				. '<p class="foot__count">' . esc_html( $count ) . ' subscribers (' . esc_html( $month ) . ')</p>'
				. '<p><a class="bp-btn" href="/newsletter/">Subscribe free</a></p></div>';
		}

		return '<footer class="bp foot bp-alt"><div class="bp-in foot__in">'
			. '<figure class="foot__pic">' . self::img( 'stages', 'Christopher Penn walks across a large stage in front of a lit screen.' ) . '</figure>'
			. '<div class="foot__main">' . $news
			. '<nav class="foot__nav" aria-label="Site"><a href="/public-speaking/">Speaking</a><a href="/newsletter/">Newsletter</a><a href="/about/">About</a><a href="/contact-me/">Contact</a></nav>'
			. '<p class="foot__meta">' . esc_html( $year ) . ', <a href="/privacy/">Privacy</a></p>'
			. '<p class="foot__credit">' . esc_html( self::PHOTO_CREDIT ) . '</p>'
			. '</div></div></footer>';
	}
}
