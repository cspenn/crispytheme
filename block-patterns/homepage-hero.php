<?php
/**
 * Homepage Hero Pattern.
 *
 * 60/40 split hero: outcome headline, transformation subhead and a dual CTA
 * (primary booking + secondary demo reel) on the left; professional still on
 * the right. Booking owns the fold — there is no speaking/newsletter split.
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
$hero_image       = get_option( 'crispytheme_hero_image', '' );
$hero_eyebrow     = get_option( 'crispytheme_hero_eyebrow', 'IBM Champion · 8× Author · 294,000+ Newsletter Subscribers' );
$hero_headline    = get_option( 'crispytheme_hero_headline', 'Equipping Global Organizations to Win the AI Revolution' );
$hero_subheadline = get_option( 'crispytheme_hero_subheadline', 'Christopher Penn shows enterprise teams how to deploy AI as a competitive advantage — not a threat.' );
$cta_primary      = get_option( 'crispytheme_hero_cta_primary_label', 'Book Christopher' );
$cta_secondary    = get_option( 'crispytheme_hero_cta_secondary_label', 'Watch Demo Reel' );
$booking_url      = function_exists( 'crispytheme_get_booking_url' ) ? crispytheme_get_booking_url() : '/contact/';

// Use placeholder if no image configured.
if ( empty( $hero_image ) ) {
	$hero_image = CRISPY_THEME_URI . '/assets/images/hero-placeholder.jpg';
}

register_block_pattern(
	'crispytheme/homepage-hero',
	[
		'title'       => __( 'Homepage Hero', 'crispy-theme' ),
		'description' => __( 'Split hero with outcome headline, transformation subhead and a dual booking/demo-reel CTA.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'hero', 'homepage', 'speaker', 'cover', 'header', 'cta' ],
		'content'     => '<!-- wp:group {"align":"full","className":"homepage-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull homepage-hero" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)">
	<!-- wp:columns {"verticalAlignment":"center","className":"homepage-hero__inner","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center homepage-hero__inner">
		<!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">
			<!-- wp:paragraph {"className":"homepage-hero-eyebrow"} -->
			<p class="homepage-hero-eyebrow">' . esc_html( $hero_eyebrow ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"homepage-hero__title","style":{"typography":{"fontSize":"clamp(3rem, 6vw, 4rem)","lineHeight":"1.1"},"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|40"}}}} -->
			<h1 class="wp-block-heading homepage-hero__title" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--40);font-size:clamp(3rem, 6vw, 4rem);line-height:1.1">' . esc_html( $hero_headline ) . '</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"homepage-hero__subhead speakable-summary","style":{"typography":{"fontSize":"1.25rem","lineHeight":"1.5"}},"textColor":"foreground-secondary"} -->
			<p class="homepage-hero__subhead speakable-summary has-foreground-secondary-color has-text-color" style="font-size:1.25rem;line-height:1.5">' . esc_html( $hero_subheadline ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"className":"homepage-hero__ctas","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}}} -->
			<div class="wp-block-buttons homepage-hero__ctas" style="margin-top:var(--wp--preset--spacing--50)">
				<!-- wp:button {"className":"is-style-fill"} -->
				<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $booking_url ) . '">' . esc_html( $cta_primary ) . '</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#demo-reel">' . esc_html( $cta_secondary ) . '</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
			<!-- wp:image {"className":"homepage-hero__image","style":{"border":{"radius":"0.5rem"}}} -->
			<figure class="wp-block-image homepage-hero__image has-custom-border"><img src="' . esc_url( $hero_image ) . '" alt="' . esc_attr__( 'Christopher Penn speaking on stage', 'crispy-theme' ) . '" style="border-radius:0.5rem"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
	]
);
