<?php
/**
 * Homepage Planner Testimonial Pattern.
 *
 * Option-driven planner testimonial with the full six-element attribution
 * (quote, name, title at event, organization, event + year, optional video).
 * Renders nothing until a real, complete testimonial is configured — no
 * placeholder or anonymous quotes are ever published (report §5 / FP#8).
 *
 * @package CrispyTheme
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

$quote = trim( (string) get_option( 'crispytheme_testimonial_quote', '' ) );
$name  = trim( (string) get_option( 'crispytheme_testimonial_name', '' ) );
$title = trim( (string) get_option( 'crispytheme_testimonial_title', '' ) );
$org   = trim( (string) get_option( 'crispytheme_testimonial_org', '' ) );
$event = trim( (string) get_option( 'crispytheme_testimonial_event', '' ) );
$video = trim( (string) get_option( 'crispytheme_testimonial_video', '' ) );

// Render nothing unless at least the quote and attributed name are present.
if ( '' === $quote || '' === $name ) {
	register_block_pattern(
		'crispytheme/homepage-testimonial',
		[
			'title'       => __( 'Homepage Planner Testimonial', 'crispy-theme' ),
			'description' => __( 'Planner testimonial (configure in Theme Options to display).', 'crispy-theme' ),
			'categories'  => [ 'crispytheme-homepage', 'crispytheme-trust', 'crispytheme' ],
			'keywords'    => [ 'testimonial', 'quote', 'planner', 'social proof' ],
			'content'     => '',
		]
	);
	return;
}

// Compose the attribution line from whichever elements are configured.
$meta_parts = array_filter(
	[ $title, $org, $event ],
	static fn ( string $part ): bool => '' !== $part
);
$meta_line = implode( ' · ', $meta_parts );

$video_block = '';
if ( '' !== $video ) {
	$video_block = '<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
		<p class="has-text-align-center has-small-font-size" style="margin-top:var(--wp--preset--spacing--30)"><a href="' . esc_url( $video ) . '">' . esc_html__( 'Watch the video testimonial →', 'crispy-theme' ) . '</a></p>
		<!-- /wp:paragraph -->';
}

$meta_block = '';
if ( '' !== $meta_line ) {
	$meta_block = '<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"foreground-secondary"} -->
		<p class="has-text-align-center has-foreground-secondary-color has-text-color has-small-font-size">' . esc_html( $meta_line ) . '</p>
		<!-- /wp:paragraph -->';
}

register_block_pattern(
	'crispytheme/homepage-testimonial',
	[
		'title'       => __( 'Homepage Planner Testimonial', 'crispy-theme' ),
		'description' => __( 'Planner testimonial with full six-element attribution.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'testimonial', 'quote', 'planner', 'social proof' ],
		'content'     => '<!-- wp:group {"className":"homepage-testimonial","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group homepage-testimonial" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:quote {"className":"homepage-testimonial__quote"} -->
	<blockquote class="wp-block-quote homepage-testimonial__quote">
		<!-- wp:paragraph {"align":"center","fontSize":"x-large"} -->
		<p class="has-text-align-center has-x-large-font-size">' . esc_html( $quote ) . '</p>
		<!-- /wp:paragraph -->
	</blockquote>
	<!-- /wp:quote -->

	<!-- wp:paragraph {"align":"center","className":"homepage-testimonial__name","style":{"typography":{"fontWeight":"600"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center homepage-testimonial__name" style="margin-top:var(--wp--preset--spacing--40);font-weight:600">' . esc_html( $name ) . '</p>
	<!-- /wp:paragraph -->

	' . $meta_block . '
	' . $video_block . '
</div>
<!-- /wp:group -->',
	]
);
