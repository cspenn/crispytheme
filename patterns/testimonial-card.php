<?php
/**
 * Testimonial Card Pattern.
 *
 * Displays a testimonial with photo, quote, and attribution.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/testimonial-card',
	[
		'title'       => __( 'Testimonial Card', 'crispy-theme' ),
		'description' => __( 'Display a testimonial with photo, quote, and attribution.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'testimonial', 'quote', 'review', 'social proof' ],
		'content'     => '<!-- wp:group {"className":"is-style-shadow-box testimonial-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group is-style-shadow-box testimonial-card" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:quote {"className":"testimonial-quote"} -->
	<blockquote class="wp-block-quote testimonial-quote">
		<p>' . esc_html__( 'Christopher Penn delivers presentations that are not only informative but immediately actionable. His ability to break down complex AI concepts into practical steps sets him apart from other speakers.', 'crispy-theme' ) . '</p>
	</blockquote>
	<!-- /wp:quote -->

	<!-- wp:group {"className":"testimonial-attribution","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
	<div class="wp-block-group testimonial-attribution">
		<!-- wp:image {"width":"60px","height":"60px","className":"testimonial-avatar","style":{"border":{"radius":"50%"}}} -->
		<figure class="wp-block-image is-resized testimonial-avatar" style="border-radius:50%"><img src="' . esc_url( CRISPY_THEME_URI . '/assets/images/placeholder-avatar.svg' ) . '" alt="' . esc_attr__( 'Testimonial Author', 'crispy-theme' ) . '" style="border-radius:50%;width:60px;height:60px"/></figure>
		<!-- /wp:image -->

		<!-- wp:group {"className":"testimonial-author-info","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
		<div class="wp-block-group testimonial-author-info">
			<!-- wp:paragraph {"className":"testimonial-author-name","style":{"typography":{"fontWeight":"600"}}} -->
			<p class="testimonial-author-name" style="font-weight:600">' . esc_html__( 'Jane Smith', 'crispy-theme' ) . '</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"testimonial-author-title","style":{"typography":{"fontSize":"0.875rem"},"color":{"text":"var:preset|color|tertiary"}}} -->
			<p class="testimonial-author-title has-tertiary-color has-text-color" style="font-size:0.875rem">' . esc_html__( 'VP of Marketing, Example Corp', 'crispy-theme' ) . '</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
