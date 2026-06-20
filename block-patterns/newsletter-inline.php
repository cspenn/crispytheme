<?php
/**
 * Inline Newsletter CTA Pattern.
 *
 * A compact newsletter signup for sidebar or after-post placement.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/newsletter-inline',
	[
		'title'       => __( 'Newsletter Inline CTA', 'crispy-theme' ),
		'description' => __( 'Compact newsletter signup for sidebar or after-post placement.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-newsletter', 'crispytheme' ],
		'keywords'    => [ 'newsletter', 'email', 'subscribe', 'signup', 'substack' ],
		'content'     => '<!-- wp:group {"className":"newsletter-inline","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
<div class="wp-block-group newsletter-inline" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h4 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Almost Timely Newsletter', 'crispy-theme' ) . '</h4>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<p style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Join 294,000+ readers getting weekly insights on AI, marketing, and analytics.', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"width":100} -->
		<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="https://almosttimely.substack.com">' . esc_html__( 'Subscribe Free', 'crispy-theme' ) . '</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
	]
);
