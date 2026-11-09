<?php
/**
 * PostEventScore Pattern.
 *
 * Aggregate post-event satisfaction callout. Renders nothing until BOTH the
 * score and engagement count are configured with verified survey data — no
 * placeholder or estimated figures are ever published (report §15 / FP#8).
 *
 * @package CrispyTheme
 * @since 1.2.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

$score  = trim( (string) get_option( 'crispytheme_posteventscore_value', '' ) );
$count  = trim( (string) get_option( 'crispytheme_posteventscore_count', '' ) );
$source = trim( (string) get_option( 'crispytheme_posteventscore_source', 'Source: post-event surveys' ) );

// Render nothing unless both verified figures are present.
if ( '' === $score || '' === $count ) {
	register_block_pattern(
		'crispytheme/posteventscore',
		[
			'title'       => __( 'PostEventScore', 'crispy-theme' ),
			'description' => __( 'Aggregate satisfaction callout (configure verified figures in Theme Options to display).', 'crispy-theme' ),
			'categories'  => [ 'crispytheme-trust', 'crispytheme' ],
			'keywords'    => [ 'posteventscore', 'satisfaction', 'social proof', 'trust' ],
			'content'     => '',
		]
	);
	return;
}

/* translators: %s: engagement count, e.g. "120+". */
$context_line = sprintf( __( 'across %s enterprise engagements', 'crispy-theme' ), $count );

register_block_pattern(
	'crispytheme/posteventscore',
	[
		'title'       => __( 'PostEventScore', 'crispy-theme' ),
		'description' => __( 'Aggregate post-event satisfaction callout.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-trust', 'crispytheme' ],
		'keywords'    => [ 'posteventscore', 'satisfaction', 'social proof', 'trust' ],
		'content'     => '<!-- wp:group {"className":"posteventscore","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"top":{"color":"var:preset|color|accent","width":"3px"}}},"backgroundColor":"background-secondary","layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group posteventscore has-background-secondary-background-color has-background" style="border-top-color:var(--wp--preset--color--accent);border-top-width:3px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:paragraph {"align":"center","className":"posteventscore__label","style":{"typography":{"fontSize":"0.9375rem"}},"textColor":"foreground-secondary"} -->
	<p class="has-text-align-center posteventscore__label has-foreground-secondary-color has-text-color" style="font-size:0.9375rem">' . esc_html__( 'Average Post-Event Satisfaction', 'crispy-theme' ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"align":"center","className":"posteventscore__value","style":{"typography":{"fontSize":"2.75rem","fontWeight":"700","lineHeight":"1.1"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|10"}}}} -->
	<p class="has-text-align-center posteventscore__value" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--10);font-size:2.75rem;font-weight:700;line-height:1.1">' . esc_html( $score ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
	<p class="has-text-align-center has-large-font-size">' . esc_html( $context_line ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"align":"center","fontSize":"small","textColor":"foreground-secondary","style":{"typography":{"fontStyle":"italic","fontWeight":"400"}}} -->
	<p class="has-text-align-center has-foreground-secondary-color has-text-color has-small-font-size" style="font-style:italic;font-weight:400">' . esc_html( $source ) . '</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
	]
);
