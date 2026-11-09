<?php
/**
 * Booking Page Pattern.
 *
 * The whole /public-speaking/ booking page: first screen, level finder, LPA letter, stages and
 * credits, fees and terms, steps, planner questions, and the form. The markup comes from
 * CrispyTheme\Booking\BookingPage and every fact comes from Theme Options.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/booking-page',
	[
		'title'       => __( 'Booking Page', 'crispy-theme' ),
		'description' => __( 'The planner booking page: level finder, proof, fees, steps, questions, and the booking form.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme' ],
		'keywords'    => [ 'booking', 'speaking', 'fees', 'form', 'planner' ],
		'content'     => \CrispyTheme\Booking\BookingPage::content(),
	]
);
