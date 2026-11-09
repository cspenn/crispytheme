<?php
/**
 * Homepage Metrics Badge Pattern.
 *
 * Compact credibility badge: newsletter reach, events delivered, and the
 * eight-time IBM Champion and 14 published books credentials. The subscriber count is sourced from
 * crispytheme_get_subscriber_count() (manual option now, API-swappable later).
 *
 * @package CrispyTheme
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

$subscriber_count = function_exists( 'crispytheme_get_subscriber_count' ) ? crispytheme_get_subscriber_count() : '';
$stat_events      = (string) get_option( 'crispytheme_stat_events', '' );

// Build the metric cells. Numeric metrics are omitted when their value is blank;
// the IBM Champion and book credentials are always shown (BookingFacts holds the counts).
$cells = [];

if ( '' !== trim( $subscriber_count ) ) {
	$subscriber_as_of = function_exists( 'crispytheme_get_subscriber_as_of' ) ? crispytheme_get_subscriber_as_of() : '';
	$cells[]          = [ $subscriber_count, '' !== $subscriber_as_of ? sprintf( /* translators: %s: month and year */ __( 'Subscribers (%s)', 'crispy-theme' ), $subscriber_as_of ) : __( 'Subscribers', 'crispy-theme' ) ];
}
if ( '' !== trim( $stat_events ) ) {
	$cells[] = [ $stat_events, __( 'Events Worldwide', 'crispy-theme' ) ];
}
$ibm_times = \CrispyTheme\Booking\BookingFacts::get( 'ibm_champion_times' );
$cells[]   = [ ucfirst( $ibm_times ) . '-time IBM Champion', sprintf( /* translators: %d: year */ __( '%d was the final year', 'crispy-theme' ), \CrispyTheme\Booking\BookingFacts::int( 'ibm_final_year' ) ) ];
$cells[]   = [
	sprintf( /* translators: %d: count of published books */ __( '%d published books', 'crispy-theme' ), \CrispyTheme\Booking\BookingFacts::int( 'book_titles' ) ),
	sprintf( /* translators: %d: count of published editions */ __( '%d editions', 'crispy-theme' ), \CrispyTheme\Booking\BookingFacts::int( 'book_editions' ) ),
];

$cell_blocks = '';
foreach ( $cells as $cell ) {
	$cell_blocks .= '<!-- wp:group {"className":"metrics-badge__item","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group metrics-badge__item">
		<!-- wp:paragraph {"align":"center","className":"metrics-badge__value","style":{"typography":{"fontSize":"2rem","fontWeight":"700","lineHeight":"1.1"}}} -->
		<p class="has-text-align-center metrics-badge__value" style="font-size:2rem;font-weight:700;line-height:1.1">' . esc_html( $cell[0] ) . '</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"align":"center","className":"metrics-badge__label","style":{"typography":{"fontSize":"0.9375rem"}},"textColor":"foreground-secondary"} -->
		<p class="has-text-align-center metrics-badge__label has-foreground-secondary-color has-text-color" style="font-size:0.9375rem">' . esc_html( $cell[1] ) . '</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	';
}

register_block_pattern(
	'crispytheme/metrics-badge',
	[
		'title'       => __( 'Homepage Metrics Badge', 'crispy-theme' ),
		'description' => __( 'Newsletter reach, events, and credential badge for the homepage.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'metrics', 'stats', 'credentials', 'subscribers', 'badge', 'trust' ],
		'content'     => '<!-- wp:group {"className":"metrics-badge","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"background-secondary","layout":{"type":"constrained","contentSize":"1100px"}} -->
<div class="wp-block-group metrics-badge has-background-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:group {"className":"metrics-badge__row","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-around"},"style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
	<div class="wp-block-group metrics-badge__row">
		' . $cell_blocks . '
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
