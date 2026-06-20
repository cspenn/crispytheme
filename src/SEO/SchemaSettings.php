<?php
/**
 * Schema Settings reader.
 *
 * The sole class allowed to call get_option() and home_url() for
 * Schema.org configuration values. Injects typed values into
 * builder classes to keep domain logic free of WordPress globals.
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * Schema Settings reader.
 */
class SchemaSettings {

	/**
	 * Get the person's full name.
	 *
	 * @return string Person name.
	 */
	public function get_person_name(): string {
		return (string) get_option( 'crispytheme_schema_person_name', 'Christopher S. Penn' );
	}

	/**
	 * Get the person's headshot image URL.
	 *
	 * @return string Image URL, or empty string if not set.
	 */
	public function get_person_image(): string {
		return (string) get_option( 'crispytheme_schema_person_image', '' );
	}

	/**
	 * Get the person's job title.
	 *
	 * @return string Job title.
	 */
	public function get_person_job_title(): string {
		return (string) get_option( 'crispytheme_schema_person_job_title', 'AI Keynote Speaker & Strategist' );
	}

	/**
	 * Get the person's description.
	 *
	 * @return string Person description.
	 */
	public function get_person_description(): string {
		return (string) get_option(
			'crispytheme_schema_person_description',
			'Christopher Penn is an internationally recognized AI keynote speaker, IBM Champion, and 8× published author. Co-founder of Trust Insights.'
		);
	}

	/**
	 * Get the sameAs URL list.
	 *
	 * Reads a newline-delimited textarea option; trims and drops empties.
	 * Handles both LF and CRLF line endings.
	 *
	 * @return array<int, string> List of sameAs URLs.
	 */
	public function get_same_as(): array {
		$raw = (string) get_option( 'crispytheme_schema_same_as', 'https://www.trustinsights.ai' );

		// explode() (unlike preg_split) never returns false, so there is no
		// unreachable defensive branch to cover. Normalize CRLF/CR to LF first.
		$normalized = str_replace( [ "\r\n", "\r" ], "\n", $raw );
		$lines      = explode( "\n", $normalized );
		$trimmed    = array_map( 'trim', $lines );

		return array_values( array_filter( $trimmed, static fn( string $url ): bool => '' !== $url ) );
	}

	/**
	 * Get the organization name.
	 *
	 * @return string Organization name.
	 */
	public function get_org_name(): string {
		return (string) get_option( 'crispytheme_schema_org_name', 'Christopher S. Penn Speaking' );
	}

	/**
	 * Get the organization logo URL.
	 *
	 * @return string Logo URL, or empty string if not set.
	 */
	public function get_org_logo(): string {
		return (string) get_option( 'crispytheme_schema_org_logo', '' );
	}

	/**
	 * Get the booking contact email.
	 *
	 * @return string Booking email, or empty string if not set.
	 */
	public function get_booking_email(): string {
		return (string) get_option( 'crispytheme_schema_booking_email', '' );
	}

	/**
	 * Get the booking URL as an absolute URL.
	 *
	 * Resolves relative paths via home_url().
	 *
	 * @return string Absolute booking URL.
	 */
	public function get_booking_url(): string {
		$path = (string) get_option( 'crispytheme_booking_url', '/contact/' );
		return home_url( $path );
	}

	/**
	 * Get the home URL.
	 *
	 * @return string Home URL.
	 */
	public function get_home_url(): string {
		return home_url( '/' );
	}

	/**
	 * Get the demo reel video URL.
	 *
	 * @return string Video URL, or empty string if not set.
	 */
	public function get_video_url(): string {
		return (string) get_option( 'crispytheme_schema_video_url', '' );
	}

	/**
	 * Get the video name.
	 *
	 * @return string Video name.
	 */
	public function get_video_name(): string {
		return (string) get_option( 'crispytheme_schema_video_name', 'Christopher Penn AI Keynote Demo Reel' );
	}

	/**
	 * Get the video description.
	 *
	 * @return string Video description, or empty string if not set.
	 */
	public function get_video_description(): string {
		return (string) get_option( 'crispytheme_schema_video_description', '' );
	}

	/**
	 * Get the video thumbnail URL.
	 *
	 * @return string Thumbnail URL, or empty string if not set.
	 */
	public function get_video_thumbnail(): string {
		return (string) get_option( 'crispytheme_schema_video_thumbnail', '' );
	}

	/**
	 * Get the video upload date.
	 *
	 * @return string Upload date string, or empty string if not set.
	 */
	public function get_video_upload_date(): string {
		return (string) get_option( 'crispytheme_schema_video_upload_date', '' );
	}

	/**
	 * Get the video duration in ISO 8601 format (e.g. PT2M30S).
	 *
	 * @return string Duration string, or empty string if not set.
	 */
	public function get_video_duration(): string {
		return (string) get_option( 'crispytheme_schema_video_duration', '' );
	}

	/**
	 * Get the current page URL.
	 *
	 * @return string Current page URL.
	 */
	public function get_current_url(): string {
		global $wp;
		return home_url( add_query_arg( [], $wp->request ?? '' ) );
	}
}
