<?php
/**
 * Newsletter Hero Pattern.
 *
 * A full-width newsletter signup section with subscriber count.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/newsletter-hero',
	[
		'title'       => __( 'Newsletter Hero', 'crispy-theme' ),
		'description' => __( 'Full-width newsletter signup section with subscriber count.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-newsletter', 'crispytheme' ],
		'keywords'    => [ 'newsletter', 'email', 'subscribe', 'signup', 'hero', 'substack' ],
		'content'     => '<!-- wp:group {"className":"newsletter-signup","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group alignfull newsletter-signup" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:group {"layout":{"type":"constrained","contentSize":"600px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"align":"center","className":"newsletter-subscriber-count","style":{"typography":{"fontSize":"3rem","fontWeight":"700"}}} -->
		<p class="has-text-align-center newsletter-subscriber-count" style="font-size:3rem;font-weight:700">294,000+</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
		<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'readers get the Almost Timely Newsletter every week', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":2,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
		<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'AI, Marketing, and Analytics Insights', 'crispy-theme' ) . '</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
		<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--40)">' . esc_html__( 'Practical, actionable insights on AI, marketing technology, and data analytics. No hype, just useful strategies you can implement immediately.', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"base","textColor":"primary","style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-base-background-color has-text-color has-background has-link-color wp-element-button" href="https://almosttimely.substack.com">' . esc_html__( 'Subscribe Free', 'crispy-theme' ) . '</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
