<?php
/**
 * Event List Pattern.
 *
 * Display upcoming speaking events with dates and locations.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/event-list',
	[
		'title'       => __( 'Event List', 'crispy-theme' ),
		'description' => __( 'Display upcoming speaking events with dates and locations.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme' ],
		'keywords'    => [ 'events', 'speaking', 'schedule', 'conference', 'upcoming' ],
		'content'     => '<!-- wp:group {"className":"event-list-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}}} -->
<div class="wp-block-group event-list-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--50)">' . esc_html__( 'Upcoming Events', 'crispy-theme' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"event-list"} -->
	<div class="wp-block-group event-list">
		<!-- wp:group {"className":"event-item","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group event-item">
			<!-- wp:group {"className":"event-date"} -->
			<div class="wp-block-group event-date">
				<span class="event-date-month">' . esc_html__( 'MAR', 'crispy-theme' ) . '</span>
				<span class="event-date-day">15</span>
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"event-details","layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group event-details">
				<!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"0"}}}} -->
				<h4 class="wp-block-heading" style="margin-bottom:0">' . esc_html__( 'Social Media Marketing World', 'crispy-theme' ) . '</h4>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|tertiary"}}} -->
				<p class="has-tertiary-color has-text-color">' . esc_html__( 'San Diego, CA', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"event-item","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group event-item">
			<!-- wp:group {"className":"event-date"} -->
			<div class="wp-block-group event-date">
				<span class="event-date-month">' . esc_html__( 'APR', 'crispy-theme' ) . '</span>
				<span class="event-date-day">22</span>
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"event-details","layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group event-details">
				<!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"0"}}}} -->
				<h4 class="wp-block-heading" style="margin-bottom:0">' . esc_html__( 'MAICON 2025', 'crispy-theme' ) . '</h4>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|tertiary"}}} -->
				<p class="has-tertiary-color has-text-color">' . esc_html__( 'Cleveland, OH', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
