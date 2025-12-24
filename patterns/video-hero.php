<?php
/**
 * Video Hero Pattern.
 *
 * Responsive video embed with title overlay for speaking reels.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/video-hero',
	[
		'title'       => __( 'Video Hero', 'crispy-theme' ),
		'description' => __( 'Responsive video embed for speaking reels and presentations.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme' ],
		'keywords'    => [ 'video', 'youtube', 'vimeo', 'speaking', 'reel', 'embed' ],
		'content'     => '<!-- wp:group {"className":"video-hero-section","align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group alignwide video-hero-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--40)">' . esc_html__( 'Speaking Reel', 'crispy-theme' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:embed {"url":"https://www.youtube.com/watch?v=rVyBhtdX6P4","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio video-hero"} -->
	<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio video-hero">
		<div class="wp-block-embed__wrapper">
			https://www.youtube.com/watch?v=rVyBhtdX6P4
		</div>
	</figure>
	<!-- /wp:embed -->
</div>
<!-- /wp:group -->',
	]
);
