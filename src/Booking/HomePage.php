<?php
/**
 * Home page renderer.
 *
 * The home page has one job: send an event planner to the booking page. It reuses the booking page
 * design system (tokens, buttons, photographs, facts) and shares its strings: the five levels, the LPA
 * letter, the stages, and the company names come from BookingPage, so each string lives once. The
 * headline and subhead stay Theme Options with the approved text as default.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Home page renderer class.
 */
final class HomePage {

	/**
	 * Name of the dynamic block that prints the page.
	 */
	public const BLOCK = 'crispytheme/home-page';

	/**
	 * Approved headline (Penn, 2026-10-25). The source of "since 2013" is in docs/client-context.md.
	 */
	public const HEADLINE = 'Helping global organizations win with AI since 2013';

	/**
	 * Approved subhead (Penn, 2026-10-24).
	 */
	public const SUBHEAD = 'Christopher S. Penn is an AI builder and keynote speaker. His keynotes and workshops teach teams the Five Levels of AI Enablement.';

	/**
	 * Approved Fortune 500 sentence (Penn, 2026-10-25).
	 */
	public const PITCH = 'Keynotes and workshops that turn AI into your audience’s competitive advantage. Christopher has worked with Fortune 500 companies including Pfizer, T-Mobile, and McDonald’s.';

	/**
	 * Label of the one primary action. It matches the booking page.
	 */
	public const ACTION = 'Check my dates';

	/**
	 * Tell if the current request shows the front page.
	 *
	 * @return bool
	 */
	public static function is_home_page(): bool {
		return function_exists( 'is_front_page' ) && is_front_page();
	}

	/**
	 * Register the dynamic block.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_block' ] );
		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_filter( 'gform_field_value_topic', [ ProblemPicker::class, 'topic_from_request' ] );
	}

	/**
	 * Register the block type.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			self::BLOCK,
			[
				'api_version'     => '3',
				'render_callback' => [ self::class, 'html' ],
			]
		);
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
	 * Get the address of the primary action: the form on the booking page.
	 *
	 * @return string
	 */
	public static function action_url(): string {
		return BookingFacts::get( 'page_path' ) . '#check-my-dates';
	}

	/**
	 * Get an option text, or the approved default when the option is blank.
	 *
	 * @param string $name     Option name.
	 * @param string $approved Approved text.
	 * @return string
	 */
	private static function text( string $name, string $approved ): string {
		$value = trim( (string) get_option( $name, $approved ) );

		return '' === $value ? $approved : $value;
	}

	/**
	 * Add the page class to the body of the front page and the booking page. The stylesheet puts the site
	 * header on the same margins as the page, so the logo and the first headline share one left edge.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( self::is_home_page() || BookingPage::is_booking_page() ) {
			$classes[] = 'bp-page';
		}

		return $classes;
	}

	/**
	 * Get the HTML of the whole page: the main element and the footer.
	 *
	 * @return string
	 */
	public static function html(): string {
		$reel     = BookingPage::reel();
		$sections = [
			self::hero(),
			Proof::reach_section( self::action_url() ),
			Proof::rooms_section( false ),
			Proof::letter_section( false, false ),
			Proof::also_section( false ),
			BookingPage::levels_section( false ),
			self::close(),
		];

		return '<main id="main" class="bp bp-home" data-facts-as-of="' . esc_attr( BookingFacts::get( 'facts_as_of' ) ) . '">' . "\n"
			. implode( "\n", $sections ) . "\n" . $reel['dialog'] . "\n</main>\n"
			. BookingPage::footer( true );
	}

	/**
	 * Section 1: first screen. The headline, the subhead, and the menu card beside one photograph.
	 *
	 * @return string
	 */
	private static function hero(): string {
		return Hero::render(
			esc_html( self::text( 'crispytheme_hero_headline', self::HEADLINE ) ),
			self::text( 'crispytheme_hero_subheadline', self::SUBHEAD ),
			BookingFacts::get( 'page_path' ) . '#fees'
		);
	}

	/**
	 * Last section: one photograph with the closing line on its calm side, then the approved pitch and the one action.
	 *
	 * @return string
	 */
	private static function close(): string {
		return '<section class="bp-sec close" aria-labelledby="hp-close-h">'
			. '<div class="bp-in close__in">'
			. '<figure class="close__pic" data-reveal>' . BookingPage::img( 'close', 'Christopher Penn speaks to a seated room, one hand raised.' ) . '</figure>'
			. '<div class="close__body">'
			. '<h2 id="hp-close-h" data-reveal>Bring Christopher to your stage</h2>'
			. '<p class="close__pitch" data-reveal>' . esc_html( self::PITCH ) . '</p>'
			. '<p data-reveal><a class="bp-btn" href="' . esc_url( self::action_url() ) . '">' . esc_html( self::ACTION ) . '</a></p>'
			. '</div></div></section>';
	}
}
