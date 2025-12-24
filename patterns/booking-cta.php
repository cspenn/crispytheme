<?php
/**
 * Booking CTA Pattern.
 *
 * Clear booking inquiry call-to-action for speaking page.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/booking-cta',
	[
		'title'       => __( 'Booking CTA', 'crispy-theme' ),
		'description' => __( 'Clear booking inquiry call-to-action for speaking page.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme' ],
		'keywords'    => [ 'booking', 'contact', 'cta', 'speaking', 'inquiry', 'hire' ],
		'content'     => '<!-- wp:group {"className":"booking-cta","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group alignfull booking-cta" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"700px"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
		<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Book Christopher for Your Event', 'crispy-theme' ) . '</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
		<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--40)">' . esc_html__( 'Bring practical AI and marketing expertise to your conference, corporate event, or executive retreat. Customized presentations for your specific audience and industry.', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"base","textColor":"primary","className":"is-style-fill","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}}} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-primary-color has-base-background-color has-text-color has-background has-link-color wp-element-button" href="/contact/">' . esc_html__( 'Request Availability', 'crispy-theme' ) . '</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.allamericanspeakers.com/speakers/442248/Christopher-Penn">' . esc_html__( 'View Speaker Bureau', 'crispy-theme' ) . '</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
