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

// Get configurable options. Every stat is blank by default and shows only when a sourced value is set.
$stat_events       = get_option( 'crispytheme_stat_events', '' );
$stat_countries    = get_option( 'crispytheme_stat_countries', '' );
$stat_satisfaction = get_option( 'crispytheme_stat_satisfaction', '' );

/**
 * Build one stat block, or an empty string when the value is blank.
 *
 * @param mixed  $value Stat value.
 * @param string $label Stat label.
 * @return string Block markup.
 */
$stat_item = static function ( $value, string $label ): string {
	if ( '' === trim( (string) $value ) ) {
		return '';
	}

	return '	<!-- wp:group {"className":"stat-item","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group stat-item">
		<!-- wp:paragraph {"align":"center","className":"stat-number","style":{"typography":{"fontSize":"2.5rem","fontWeight":"700"}},"textColor":"primary"} -->
		<p class="has-text-align-center stat-number has-primary-color has-text-color" style="font-size:2.5rem;font-weight:700">' . esc_html( (string) $value ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","className":"stat-label","style":{"typography":{"fontSize":"0.9375rem"}}} -->
		<p class="has-text-align-center stat-label" style="font-size:0.9375rem">' . esc_html( $label ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
';
};

$stat_blocks = $stat_item( $stat_events, __( 'Events', 'crispy-theme' ) )
	. $stat_item( $stat_countries, __( 'Countries', 'crispy-theme' ) )
	. $stat_item( $stat_satisfaction, __( 'Satisfaction', 'crispy-theme' ) );

register_block_pattern(
	'crispytheme/speaking-stats',
	[
		'title'       => __( 'Speaking Stats Bar', 'crispy-theme' ),
		'description' => __( 'Display key speaking statistics in a horizontal bar.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'stats', 'statistics', 'numbers', 'speaking', 'credentials' ],
		'content'     => '<!-- wp:group {"className":"speaking-stats-bar","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-around"}} -->
<div class="wp-block-group speaking-stats-bar" style="padding:var(--wp--preset--spacing--50) var(--wp--preset--spacing--40)">
' . $stat_blocks . '
</div>
<!-- /wp:group -->',
	]
);
