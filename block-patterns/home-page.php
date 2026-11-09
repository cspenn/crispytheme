<?php
/**
 * Home Page Pattern.
 *
 * The whole home page: one picture, the approved headline, the five levels as a ladder, proof, one
 * booking action, and one quiet newsletter section. The markup comes from CrispyTheme\Booking\HomePage.
 *
 * @package CrispyTheme
 * @since 2.1.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/home-page',
	[
		'title'       => __( 'Home Page', 'crispy-theme' ),
		'description' => __( 'The home page: headline, picture, the five levels ladder, proof, and one route to the booking page.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'home', 'hero', 'speaker', 'booking', 'ladder' ],
		'content'     => \CrispyTheme\Booking\HomePage::content(),
	]
);
