<?php
/**
 * Schema feed for the CrispySEO plugin.
 *
 * The plugin owns the JSON-LD graph. It asks the theme for the facts through the filter
 * `crispy_seo_speaker_profile`. Every value here comes from BookingFacts, BookingPage, HomePage,
 * or a Theme Option, so the schema and the visible page share one source and cannot drift.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

namespace CrispyTheme\Booking;

/**
 * Schema feed class.
 */
final class SchemaFeed {

	/**
	 * Filter name that the plugin applies.
	 */
	public const FILTER = 'crispy_seo_speaker_profile';

	/**
	 * Default name of the site entity (Organization and WebSite). It is a name, not the site title,
	 * and it does not follow a later title change. Source: docs/client-context.md, speaking title.
	 */
	public const ENTITY_NAME = 'Christopher S. Penn';

	/**
	 * Default sameAs list: the profile links that the About page prints.
	 *
	 * @var string[]
	 */
	public const SAME_AS = [
		'https://www.linkedin.com/in/christopherspenn/',
		'https://almosttimely.substack.com',
	];

	/**
	 * Pages that llms.txt lists, as paths. The plugin resolves the titles at run time.
	 *
	 * @var string[]
	 */
	public const KEY_PAGES = [ '/about/', '/newsletter/', '/contact-me/' ];

	/**
	 * Register the filter.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( self::FILTER, [ $this, 'profile' ] );
	}

	/**
	 * Add the speaker facts to the profile that the plugin builds.
	 *
	 * @param mixed $profile Profile so far.
	 * @return array<string, mixed>
	 */
	public function profile( $profile ): array {
		$profile = is_array( $profile ) ? $profile : [];
		$path    = '/' . trim( BookingFacts::get( 'page_path' ), '/' ) . '/';

		$profile['organization_name'] = trim( (string) get_option( 'crispytheme_schema_org_name', self::ENTITY_NAME ) );
		$profile['person']            = $this->person();
		$profile['booking_url']       = home_url( $path . '#check-my-dates' );
		$profile['summary']           = $this->summary();
		$profile['levels']            = $this->levels();
		$profile['key_pages']         = array_merge( [ $path ], self::KEY_PAGES );
		$profile['faq']               = [];
		$profile['video']             = [];
		$profile['speakable']         = [];
		$is_booking                   = BookingPage::is_booking_page();
		$is_front                     = function_exists( 'is_front_page' ) && is_front_page();

		if ( $is_booking ) {
			$profile['faq'] = self::faq();
		}

		if ( $is_booking || $is_front ) {
			$profile['video'] = $this->video();
		}

		if ( $is_front ) {
			$profile['speakable'] = [ '.speakable-summary' ];
		}

		return $profile;
	}

	/**
	 * Get the visible FAQ as question and answer pairs. The booking page prints the same list.
	 *
	 * @return array<int, array{question: string, answer: string}>
	 */
	public static function faq(): array {
		$out = [];

		foreach ( BookingPage::faq_items() as $item ) {
			$out[] = [
				'question' => $item[0],
				'answer'   => $item[1],
			];
		}

		return $out;
	}

	/**
	 * Build the Person facts.
	 *
	 * @return array<string, mixed>
	 */
	private function person(): array {
		$image = (string) get_option( 'crispytheme_schema_person_image', '' );

		if ( '' === $image ) {
			$image = BookingPage::photo_url( 'hero-portrait' );
		}

		return [
			'name'        => (string) get_option( 'crispytheme_schema_person_name', 'Christopher S. Penn' ),
			'job_title'   => (string) get_option( 'crispytheme_schema_person_job_title', 'AI builder and keynote speaker' ),
			'image'       => $image,
			'same_as'     => $this->same_as(),
			'knows_about' => array_merge( [ BookingPage::LEAD_TALK ], BookingPage::ALTERNATE_TALKS ),
			'credentials' => [
				[
					'name'        => 'IBM Champion',
					'description' => sprintf(
						'%1$s-time IBM Champion (%2$s was the final year)',
						ucfirst( BookingFacts::get( 'ibm_champion_times' ) ),
						BookingFacts::get( 'ibm_final_year' )
					),
				],
			],
		];
	}

	/**
	 * Read the sameAs list: the theme option, one URL per line, else the default list.
	 *
	 * @return string[]
	 */
	private function same_as(): array {
		$raw = (string) get_option( 'crispytheme_schema_same_as', implode( "\n", self::SAME_AS ) );
		$raw = str_replace( [ "\r\n", "\r" ], "\n", $raw );

		return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ), static fn( string $url ): bool => '' !== $url ) );
	}

	/**
	 * Get the one-sentence summary of the site (the home subhead).
	 *
	 * @return string
	 */
	private function summary(): string {
		$text = trim( (string) get_option( 'crispytheme_hero_subheadline', HomePage::SUBHEAD ) );

		return '' === $text ? HomePage::SUBHEAD : $text;
	}

	/**
	 * Get the Five Levels as plain lines.
	 *
	 * @return array{title: string, items: string[]}
	 */
	private function levels(): array {
		$items = [];

		foreach ( BookingPage::levels() as $level ) {
			$items[] = $level['level'] . ', ' . $level['name'] . ': ' . $level['line'];
		}

		return [
			'title' => BookingPage::LEAD_TALK,
			'items' => $items,
		];
	}

	/**
	 * Build the reel facts. Empty when the reel option is blank. The upload date comes from the theme
	 * option, else from the date of the Media Library item that holds the reel. No file states it.
	 * The duration comes from the theme option only.
	 *
	 * @return array<string, string>
	 */
	private function video(): array {
		$url = BookingFacts::get( 'reel_url' );

		if ( '' === $url ) {
			return [];
		}

		return [
			'name'        => (string) get_option( 'crispytheme_schema_video_name', 'Christopher Penn AI Keynote Demo Reel' ),
			'description' => (string) get_option( 'crispytheme_schema_video_description', '' ),
			'content_url' => $url,
			'thumbnail'   => BookingFacts::get( 'reel_poster' ),
			'upload_date' => $this->upload_date( $url ),
			'duration'    => (string) get_option( 'crispytheme_schema_video_duration', '' ),
		];
	}

	/**
	 * Get the upload date of the reel: the theme option, else the date of its Media Library item.
	 *
	 * @param string $url Reel file URL.
	 * @return string ISO 8601 date, or an empty string when neither source has one.
	 */
	private function upload_date( string $url ): string {
		$date = trim( (string) get_option( 'crispytheme_schema_video_upload_date', '' ) );

		if ( '' !== $date ) {
			return $date;
		}

		$id = function_exists( 'attachment_url_to_postid' ) ? (int) attachment_url_to_postid( $url ) : 0;

		return $id > 0 ? (string) get_post_time( 'c', false, $id ) : '';
	}
}
