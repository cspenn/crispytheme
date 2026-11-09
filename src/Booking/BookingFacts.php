<?php
/**
 * Booking page facts.
 *
 * One source of truth for every number, price, term, and credential on the booking page.
 * Each fact is a Theme Option with a default. The defaults hold the values that Christopher
 * Penn stated on 2026-10-25, 2026-10-26, and 2026-11-08 (the keynote fee). The `facts_as_of` option carries the date of that check, and the
 * page prints it as a data attribute so a stale fact is easy to find.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Booking page facts class.
 */
final class BookingFacts {

	/**
	 * Option name prefix.
	 */
	public const PREFIX = 'crispytheme_booking_';

	/**
	 * Fact definitions: key => label, default, kind (text, number, textarea, url), description.
	 *
	 * @return array<string, array{label: string, default: string, kind: string, description: string}>
	 */
	public static function definitions(): array {
		return [
			'fee_keynote_from'      => self::def( 'Keynote fee, from', '25000', 'number', 'US dollars. Keynotes start at this fee.' ),
			'fee_workshop_from'     => self::def( 'Workshop fee, from', '25000', 'number', 'US dollars. Workshops start at this fee.' ),
			'deposit_percent'       => self::def( 'Deposit percent', '50', 'number', 'Share of the fee due at signature.' ),
			'refund_over_6_months'  => self::def( 'Refund, more than 6 months before the event', '75', 'number', 'Percent of the fee refunded.' ),
			'refund_3_to_6_months'  => self::def( 'Refund, 3 to 6 months before the event', '50', 'number', 'Percent of the fee refunded.' ),
			'refund_under_3_months' => self::def( 'Refund, under 3 months before the event', '0', 'number', 'Percent of the fee refunded.' ),
			'balance_days'          => self::def( 'Balance due, days before the event', '30', 'number', 'The balance of the fee falls due this many days before the event.' ),
			'reimburse_days'        => self::def( 'Travel receipts settled, days after the event', '30', 'number', 'The client settles travel receipts within this many days of the event.' ),
			'fit_call_minutes'      => self::def( 'Fit call, minutes', '30', 'number', 'Average length of the fit call with Trust Insights business development.' ),
			'prep_call_minutes'     => self::def( 'Prep call, minutes', '30', 'number', 'Length of the prep call after signing.' ),
			'ibm_champion_times'    => self::def( 'IBM Champion count, in words', 'eight', 'text', 'Written as a word, for example: eight.' ),
			'ibm_final_year'        => self::def( 'IBM Champion final year', '2025', 'number', 'The last year of the IBM Champion program.' ),
			'book_titles'           => self::def( 'Published book titles', '14', 'number', 'Count of published titles.' ),
			'book_editions'         => self::def( 'Published book editions', '19', 'number', 'Count of published editions.' ),
			'client_names'          => self::def( 'Company names', "Pfizer, McDonald's, T-Mobile, Google, Cisco Investments, JLL, McKinsey, Twitter, Okta, Elanco, Fresenius, Sprinklr, Demandbase, CommScope, Gentex", 'textarea', 'Comma-separated company names. Names only: no work, fee, date, or scope.' ),
			'linkedin_followers'    => self::def( 'LinkedIn followers', '49269', 'number', 'Followers of the LinkedIn profile of Christopher Penn. Shown with the month in the next field.' ),
			'linkedin_as_of'        => self::def( 'LinkedIn followers, month of the count', 'October 2026', 'text', 'Month and year of the follower count, shown beside it. Update it with the count.' ),
			'quote_date'            => self::def( 'LPA letter date', 'April 2024', 'text', 'Month and year of the LPA letter.' ),
			'form_id'               => self::def( 'Gravity Forms form ID', '5', 'number', 'The booking form that the page shows.' ),
			'page_path'             => self::def( 'Booking page path', '/public-speaking/', 'text', 'Path of the booking page. The level finder links use it.' ),
			'reel_url'              => self::def( 'Demo reel file URL', '', 'url', 'MP4 file of the demo reel. Leave blank to hide the Play the reel control.' ),
			'reel_poster'           => self::def( 'Demo reel poster URL', '', 'url', 'Poster image of the demo reel.' ),
			'reel_loop_url'         => self::def( 'Reel loop file URL', '', 'url', 'MP4 file of a short silent loop cut from the reel. Leave blank to show a photograph instead.' ),
			'facts_as_of'           => self::def( 'Facts checked on', '2026-10-26', 'text', 'Date (YYYY-MM-DD) when Christopher Penn last confirmed these facts.' ),
		];
	}

	/**
	 * Build one definition.
	 *
	 * @param string $label       Admin label.
	 * @param string $default_value Default value.
	 * @param string $kind        Field kind.
	 * @param string $description Admin help text.
	 * @return array{label: string, default: string, kind: string, description: string}
	 */
	private static function def( string $label, string $default_value, string $kind, string $description ): array {
		return [
			'label'       => $label,
			'default'     => $default_value,
			'kind'        => $kind,
			'description' => $description,
		];
	}

	/**
	 * Get the full option name of a fact.
	 *
	 * @param string $key Fact key.
	 * @return string
	 */
	public static function option_name( string $key ): string {
		return self::PREFIX . $key;
	}

	/**
	 * Get a fact. A blank option falls back to the default, except for the optional reel fields.
	 *
	 * @param string $key Fact key.
	 * @return string
	 */
	public static function get( string $key ): string {
		$definitions = self::definitions();
		$default     = $definitions[ $key ]['default'] ?? '';
		$value       = trim( (string) get_option( self::option_name( $key ), $default ) );

		if ( '' === $value && ! in_array( $key, [ 'reel_url', 'reel_poster', 'reel_loop_url' ], true ) ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Get a numeric fact as an integer.
	 *
	 * @param string $key Fact key.
	 * @return int
	 */
	public static function int( string $key ): int {
		return (int) preg_replace( '/[^0-9]/', '', self::get( $key ) );
	}

	/**
	 * Format a dollar fact, for example 25000 becomes $25,000.
	 *
	 * @param string $key Fact key.
	 * @return string
	 */
	public static function money( string $key ): string {
		return '$' . number_format( self::int( $key ) );
	}

	/**
	 * Format a percent fact, for example 50 becomes 50%.
	 *
	 * @param string $key Fact key.
	 * @return string
	 */
	public static function percent( string $key ): string {
		return self::int( $key ) . '%';
	}
}
