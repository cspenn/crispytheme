<?php
/**
 * Client Logos Grid Pattern.
 *
 * Displays client/partner logos in a responsive grid with grayscale-to-color hover effect.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/client-logos',
	[
		'title'       => __( 'Client Logos Grid', 'crispy-theme' ),
		'description' => __( 'Display client or partner logos in a responsive grid.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'logos', 'clients', 'partners', 'trust', 'brands' ],
		'content'     => '<!-- wp:group {"className":"client-logo-grid","layout":{"type":"constrained"}} -->
<div class="wp-block-group client-logo-grid">
	<!-- wp:heading {"textAlign":"center","level":3} -->
	<h3 class="wp-block-heading has-text-align-center">' . esc_html__( 'Trusted By Industry Leaders', 'crispy-theme' ) . '</h3>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"client-logo-row","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group client-logo-row">
		<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-logo.svg' ) . '" alt="' . esc_attr__( 'Client Logo', 'crispy-theme' ) . '"/></figure>
		<!-- /wp:image -->

		<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-logo.svg' ) . '" alt="' . esc_attr__( 'Client Logo', 'crispy-theme' ) . '"/></figure>
		<!-- /wp:image -->

		<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-logo.svg' ) . '" alt="' . esc_attr__( 'Client Logo', 'crispy-theme' ) . '"/></figure>
		<!-- /wp:image -->

		<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-logo.svg' ) . '" alt="' . esc_attr__( 'Client Logo', 'crispy-theme' ) . '"/></figure>
		<!-- /wp:image -->

		<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-logo.svg' ) . '" alt="' . esc_attr__( 'Client Logo', 'crispy-theme' ) . '"/></figure>
		<!-- /wp:image -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
