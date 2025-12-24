<?php
/**
 * Keynote Card Pattern.
 *
 * Display a keynote topic with title, description, and audience.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/keynote-card',
	[
		'title'       => __( 'Keynote Card', 'crispy-theme' ),
		'description' => __( 'Display a keynote topic with title, description, and target audience.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-speaking', 'crispytheme' ],
		'keywords'    => [ 'keynote', 'speaking', 'presentation', 'topic', 'talk' ],
		'content'     => '<!-- wp:group {"className":"keynote-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
<div class="wp-block-group keynote-card" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:paragraph {"className":"keynote-audience"} -->
	<p class="keynote-audience">' . esc_html__( 'For Executive Leadership', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h3 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'The Intelligence Revolution', 'crispy-theme' ) . '</h3>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p>' . esc_html__( 'How Large Language Models Have Changed Business Forever. Learn what AI can and cannot do, and build a practical roadmap for your organization.', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:list {"className":"keynote-outcomes"} -->
	<ul class="keynote-outcomes">
		<li>' . esc_html__( 'Understand what generative AI actually does', 'crispy-theme' ) . '</li>
		<li>' . esc_html__( 'Identify high-impact use cases for your industry', 'crispy-theme' ) . '</li>
		<li>' . esc_html__( 'Build an AI governance framework', 'crispy-theme' ) . '</li>
	</ul>
	<!-- /wp:list -->
</div>
<!-- /wp:group -->',
	]
);
