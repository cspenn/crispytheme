<?php
/**
 * Speaking Stats Bar Pattern.
 *
 * Horizontal statistics bar showing key speaker credentials.
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
$stat_events       = get_option( 'crispytheme_stat_events', '500+' );
$stat_countries    = get_option( 'crispytheme_stat_countries', '25' );
$stat_satisfaction = get_option( 'crispytheme_stat_satisfaction', '98%' );

register_block_pattern(
	'crispytheme/speaking-stats',
	[
		'title'       => __( 'Speaking Stats Bar', 'crispy-theme' ),
		'description' => __( 'Display key speaking statistics in a horizontal bar.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'stats', 'statistics', 'numbers', 'speaking', 'credentials' ],
		'content'     => '<!-- wp:group {"className":"speaking-stats-bar","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-around"}} -->
<div class="wp-block-group speaking-stats-bar" style="padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
	<!-- wp:group {"className":"stat-item","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group stat-item">
		<!-- wp:paragraph {"align":"center","className":"stat-number","style":{"typography":{"fontSize":"2.5rem","fontWeight":"700"}},"textColor":"primary"} -->
		<p class="has-text-align-center stat-number has-primary-color has-text-color" style="font-size:2.5rem;font-weight:700">' . esc_html( $stat_events ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","className":"stat-label","style":{"typography":{"fontSize":"0.875rem","textTransform":"uppercase","letterSpacing":"0.1em"}}} -->
		<p class="has-text-align-center stat-label" style="font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em">' . esc_html__( 'Events', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"stat-item","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group stat-item">
		<!-- wp:paragraph {"align":"center","className":"stat-number","style":{"typography":{"fontSize":"2.5rem","fontWeight":"700"}},"textColor":"primary"} -->
		<p class="has-text-align-center stat-number has-primary-color has-text-color" style="font-size:2.5rem;font-weight:700">' . esc_html( $stat_countries ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","className":"stat-label","style":{"typography":{"fontSize":"0.875rem","textTransform":"uppercase","letterSpacing":"0.1em"}}} -->
		<p class="has-text-align-center stat-label" style="font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em">' . esc_html__( 'Countries', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"stat-item","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group stat-item">
		<!-- wp:paragraph {"align":"center","className":"stat-number","style":{"typography":{"fontSize":"2.5rem","fontWeight":"700"}},"textColor":"primary"} -->
		<p class="has-text-align-center stat-number has-primary-color has-text-color" style="font-size:2.5rem;font-weight:700">' . esc_html( $stat_satisfaction ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","className":"stat-label","style":{"typography":{"fontSize":"0.875rem","textTransform":"uppercase","letterSpacing":"0.1em"}}} -->
		<p class="has-text-align-center stat-label" style="font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em">' . esc_html__( 'Satisfaction', 'crispy-theme' ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
