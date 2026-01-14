<?php
/**
 * Homepage Client Showcase Pattern.
 *
 * Simplified client logo parade for homepage (up to 6 top-tier logos in single row).
 * Only displays when at least one logo is configured in Theme Options.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

// Build logo array from options (only include configured logos).
$logos = [];
for ( $i = 1; $i <= 6; $i++ ) {
	$logo_url = get_option( 'crispytheme_client_logo_' . $i, '' );
	if ( ! empty( $logo_url ) ) {
		$client_names = [
			1 => 'IBM',
			2 => 'Cisco',
			3 => 'T-Mobile',
			4 => "McDonald's",
			5 => 'GoDaddy',
			6 => 'AAA',
		];
		$logos[] = [
			'url'  => $logo_url,
			'name' => $client_names[ $i ] ?? __( 'Client', 'crispy-theme' ),
		];
	}
}

// Only register pattern if at least one logo is configured.
if ( empty( $logos ) ) {
	// Register empty pattern that renders nothing.
	register_block_pattern(
		'crispytheme/homepage-clients',
		[
			'title'       => __( 'Homepage Client Showcase', 'crispy-theme' ),
			'description' => __( 'Client logo display (configure logos in Theme Options to display).', 'crispy-theme' ),
			'categories'  => [ 'crispytheme-homepage', 'crispytheme-trust', 'crispytheme' ],
			'keywords'    => [ 'logos', 'clients', 'brands', 'trust', 'homepage' ],
			'content'     => '', // Empty content when no logos configured.
		]
	);
	return;
}

// Build logo blocks dynamically.
$logo_blocks = '';
foreach ( $logos as $logo ) {
	$logo_blocks .= '<!-- wp:image {"className":"client-logo","sizeSlug":"medium"} -->
		<figure class="wp-block-image size-medium client-logo"><img src="' . esc_url( $logo['url'] ) . '" alt="' . esc_attr( $logo['name'] ) . '"/></figure>
		<!-- /wp:image -->

		';
}

register_block_pattern(
	'crispytheme/homepage-clients',
	[
		'title'       => __( 'Homepage Client Showcase', 'crispy-theme' ),
		'description' => __( 'Simplified client logo display for homepage with top-tier brands.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'logos', 'clients', 'brands', 'trust', 'homepage' ],
		'content'     => '<!-- wp:group {"className":"homepage-clients","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group homepage-clients" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:paragraph {"align":"center","className":"homepage-clients-label","style":{"typography":{"fontSize":"0.875rem","textTransform":"uppercase","letterSpacing":"0.1em"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center homepage-clients-label" style="margin-bottom:var(--wp--preset--spacing--40);font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em">' . esc_html__( 'Trusted by industry leaders', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"homepage-clients-row","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group homepage-clients-row">
		' . $logo_blocks . '
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
