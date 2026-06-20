<?php
/**
 * Template helper functions used by block patterns and templates.
 *
 * These live in the global namespace because block pattern files are
 * included (and therefore execute) in the global scope.
 *
 * @package CrispyTheme
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! function_exists( 'crispytheme_get_subscriber_count' ) ) {
	/**
	 * Get the Almost Timely newsletter subscriber count for display.
	 *
	 * Returns a cached value if a future email-platform integration has
	 * populated the `crispytheme_subscriber_count` transient; otherwise it
	 * falls back to the manually configured option. The
	 * `crispytheme_subscriber_count` filter lets an integration override the
	 * value entirely without touching the theme.
	 *
	 * @return string The subscriber count display string (may be empty).
	 */
	function crispytheme_get_subscriber_count(): string {
		$cached = get_transient( 'crispytheme_subscriber_count' );

		if ( is_string( $cached ) && '' !== $cached ) {
			$value = $cached;
		} else {
			$value = (string) get_option( 'crispytheme_newsletter_subscriber_count', '294,000+' );
		}

		/**
		 * Filters the displayed newsletter subscriber count.
		 *
		 * A future email-platform API client can hook this to return a live
		 * count (and should cache it in the `crispytheme_subscriber_count`
		 * transient to avoid per-request API calls).
		 *
		 * @param string $value The subscriber count string.
		 */
		$value = apply_filters( 'crispytheme_subscriber_count', $value );

		return trim( (string) $value );
	}
}

if ( ! function_exists( 'crispytheme_copyright_shortcode' ) ) {
	/**
	 * Render a copyright line with the current year and site name.
	 *
	 * Used via the [crispy_copyright] shortcode inside a core/shortcode block,
	 * which reliably reflects the current year on every view (unlike post-date,
	 * which has no value on non-singular templates such as the blog index).
	 *
	 * @return string The escaped copyright line.
	 */
	function crispytheme_copyright_shortcode(): string {
		return sprintf(
			'&copy; %1$s %2$s',
			esc_html( wp_date( 'Y' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'crispy_copyright', 'crispytheme_copyright_shortcode' );
}

if ( ! function_exists( 'crispytheme_get_booking_url' ) ) {
	/**
	 * Get the configured booking CTA URL.
	 *
	 * Defaults to the existing contact path until a dedicated
	 * /book-christopher page is built, at which point only the option needs
	 * to change for every CTA on the site to repoint.
	 *
	 * @return string The booking URL.
	 */
	function crispytheme_get_booking_url(): string {
		$url = (string) get_option( 'crispytheme_booking_url', '/contact/' );

		return '' !== trim( $url ) ? $url : '/contact/';
	}
}
