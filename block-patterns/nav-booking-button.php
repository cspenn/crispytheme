<?php
/**
 * Navigation Booking Button Pattern.
 *
 * Persistent cyan "Book Christopher" CTA used in the site header. Lives as a
 * pattern (not raw template-part markup) so the booking URL stays driven by
 * the single crispytheme_booking_url option and repoints everywhere at once.
 *
 * @package CrispyTheme
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

$booking_url = function_exists( 'crispytheme_get_booking_url' ) ? crispytheme_get_booking_url() : '/contact/';
$cta_label   = get_option( 'crispytheme_hero_cta_primary_label', 'Book Christopher' );

register_block_pattern(
	'crispytheme/nav-booking-button',
	[
		'title'       => __( 'Header Booking Button', 'crispy-theme' ),
		'description' => __( 'Persistent cyan "Book Christopher" button for the site header.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'inserter'    => false,
		'content'     => '<!-- wp:buttons {"className":"site-header__cta"} -->
<div class="wp-block-buttons site-header__cta">
	<!-- wp:button {"className":"is-style-fill"} -->
	<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $booking_url ) . '">' . esc_html( $cta_label ) . ' →</a></div>
	<!-- /wp:button -->
</div>
<!-- /wp:buttons -->',
	]
);
