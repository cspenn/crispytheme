<?php
/**
 * Homepage Booking Block Pattern.
 *
 * Replaces the old four-equal-card "Where would you like to go?" grid with a
 * single dominant booking action, followed by a visually subordinate 3-ICP
 * routing row (Planners / Enterprise / Practitioners). Ends the four-way
 * attention split while preserving lightweight audience routing.
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
	'crispytheme/homepage-booking',
	[
		'title'       => __( 'Homepage Booking Block', 'crispy-theme' ),
		'description' => __( 'Dominant booking CTA with a subordinate 3-ICP routing row.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'booking', 'cta', 'conversion', 'routing', 'icp' ],
		'content'     => '<!-- wp:group {"align":"full","className":"homepage-booking","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group alignfull homepage-booking" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Bring Christopher to Your Stage', 'crispy-theme' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"large","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"textColor":"foreground-secondary"} -->
	<p class="has-text-align-center has-foreground-secondary-color has-text-color has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--50)">' . esc_html__( 'Keynotes and workshops that turn AI from a threat into your audience\'s competitive advantage — delivered for Fortune 500 organizations worldwide.', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"className":"is-style-fill","style":{"typography":{"fontSize":"1.125rem"}}} -->
		<div class="wp-block-button is-style-fill" style="font-size:1.125rem"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $booking_url ) . '">' . esc_html( $cta_label ) . '</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"foreground-secondary","className":"homepage-booking__routing-label","style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|20"}}}} -->
	<p class="homepage-booking__routing-label has-text-align-center has-foreground-secondary-color has-text-color has-small-font-size" style="margin-top:var(--wp--preset--spacing--60);margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Not ready to book? Find your path:', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"homepage-booking__routing","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
	<div class="wp-block-group homepage-booking__routing">
		<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
		<p class="has-text-align-center has-small-font-size"><a href="' . esc_url( $booking_url ) . '">' . esc_html__( 'For event planners →', 'crispy-theme' ) . '</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
		<p class="has-text-align-center has-small-font-size"><a href="/public-speaking/">' . esc_html__( 'For enterprise leaders →', 'crispy-theme' ) . '</a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
		<p class="has-text-align-center has-small-font-size"><a href="/blog/">' . esc_html__( 'For technical practitioners →', 'crispy-theme' ) . '</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
