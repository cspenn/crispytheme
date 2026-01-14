<?php
/**
 * Homepage Hero Pattern.
 *
 * Full-width hero section with on-stage photography and confident positioning statement.
 * Content is configurable via Theme Options.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

// Get configurable options with defaults.
$hero_image      = get_option( 'crispytheme_hero_image', '' );
$hero_eyebrow    = get_option( 'crispytheme_hero_eyebrow', 'IBM Champion · 8× Author · 294,000+ Newsletter Subscribers' );
$hero_headline   = get_option( 'crispytheme_hero_headline', 'Data-Driven Marketing Strategist & AI Implementation Expert' );
$hero_subheadline = get_option( 'crispytheme_hero_subheadline', 'Practitioner-scholar approach to AI, marketing analytics, and data science. No hype, just results.' );

// Use placeholder if no image configured.
if ( empty( $hero_image ) ) {
	$hero_image = CRISPY_THEME_URI . '/assets/images/hero-placeholder.jpg';
}

register_block_pattern(
	'crispytheme/homepage-hero',
	[
		'title'       => __( 'Homepage Hero', 'crispy-theme' ),
		'description' => __( 'Full-width hero section with on-stage presence and positioning statement.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'hero', 'homepage', 'speaker', 'cover', 'header' ],
		'content'     => '<!-- wp:cover {"url":"' . esc_url( $hero_image ) . '","dimRatio":75,"overlayColor":"contrast","isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","align":"full","className":"homepage-hero"} -->
<div class="wp-block-cover alignfull homepage-hero" style="min-height:70vh">
	<span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim-80 has-background-dim"></span>
	<img class="wp-block-cover__image-background" alt="' . esc_attr__( 'Christopher Penn speaking on stage', 'crispy-theme' ) . '" src="' . esc_url( $hero_image ) . '" data-object-fit="cover"/>
	<div class="wp-block-cover__inner-container">
		<!-- wp:group {"className":"homepage-hero-content","layout":{"type":"constrained","contentSize":"800px"}} -->
		<div class="wp-block-group homepage-hero-content">
			<!-- wp:paragraph {"align":"center","className":"homepage-hero-eyebrow"} -->
			<p class="has-text-align-center homepage-hero-eyebrow">' . esc_html( $hero_eyebrow ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2rem, 5vw, 3.5rem)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<h1 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30);font-size:clamp(2rem, 5vw, 3.5rem)">' . esc_html( $hero_headline ) . '</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"}}} -->
			<p class="has-text-align-center" style="font-size:1.25rem">' . esc_html( $hero_subheadline ) . '</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
</div>
<!-- /wp:cover -->',
	]
);
