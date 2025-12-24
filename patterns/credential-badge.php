<?php
/**
 * Credential Badge Pattern.
 *
 * Displays a credential or certification badge with description.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/credential-badge',
	[
		'title'       => __( 'Credential Badge', 'crispy-theme' ),
		'description' => __( 'Display a credential or certification badge with description.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'credential', 'badge', 'certification', 'award', 'IBM Champion' ],
		'content'     => '<!-- wp:group {"className":"credential-badge","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<div class="wp-block-group credential-badge">
	<!-- wp:image {"width":"80px","className":"credential-badge-image"} -->
	<figure class="wp-block-image is-resized credential-badge-image"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-badge.svg' ) . '" alt="' . esc_attr__( 'Credential Badge', 'crispy-theme' ) . '" style="width:80px"/></figure>
	<!-- /wp:image -->

	<!-- wp:group {"className":"credential-info","layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group credential-info">
		<!-- wp:paragraph {"className":"credential-title","style":{"typography":{"fontWeight":"600","fontSize":"1.125rem"}}} -->
		<p class="credential-title" style="font-weight:600;font-size:1.125rem">' . esc_html__( '8-Time IBM Champion', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"credential-description","style":{"color":{"text":"var:preset|color|tertiary"}}} -->
		<p class="credential-description has-tertiary-color has-text-color">' . esc_html__( 'Recognized for technical community leadership in IBM Data and AI (2025)', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
