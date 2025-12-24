<?php
/**
 * Prompt and Output Pattern.
 *
 * Side-by-side display of AI prompt and its output.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/prompt-output',
	[
		'title'       => __( 'Prompt and Output', 'crispy-theme' ),
		'description' => __( 'Side-by-side display of AI prompt and its output.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-code', 'crispytheme' ],
		'keywords'    => [ 'prompt', 'output', 'AI', 'GPT', 'response', 'comparison' ],
		'content'     => '<!-- wp:group {"className":"prompt-output","align":"wide"} -->
<div class="wp-block-group alignwide prompt-output">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"prompt-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
			<div class="wp-block-group prompt-section" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
				<!-- wp:paragraph {"className":"prompt-label"} -->
				<p class="prompt-label">' . esc_html__( 'PROMPT', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p>' . esc_html__( 'You are an expert marketing analyst. Analyze the following campaign data and provide 3 actionable recommendations to improve ROI. Focus on audience targeting, messaging, and channel optimization.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"output-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
			<div class="wp-block-group output-section" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
				<!-- wp:paragraph {"className":"output-label"} -->
				<p class="output-label">' . esc_html__( 'OUTPUT', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><strong>' . esc_html__( 'Recommendation 1: Refine Audience Targeting', 'crispy-theme' ) . '</strong><br>' . esc_html__( 'Your current campaigns show a 15% higher conversion rate among users aged 35-44. Consider reallocating 20% of budget to this demographic.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><strong>' . esc_html__( 'Recommendation 2: Optimize Messaging', 'crispy-theme' ) . '</strong><br>' . esc_html__( 'A/B tests indicate that benefit-focused headlines outperform feature-focused ones by 23%. Prioritize value propositions.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><strong>' . esc_html__( 'Recommendation 3: Channel Reallocation', 'crispy-theme' ) . '</strong><br>' . esc_html__( 'LinkedIn is delivering 2.3x ROAS compared to Twitter. Consider shifting 30% of Twitter budget to LinkedIn.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
	]
);
