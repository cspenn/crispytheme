<?php
/**
 * Audience Routing Pattern.
 *
 * Four-card navigation grid for routing visitors to their destination.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/audience-routing',
	[
		'title'       => __( 'Audience Routing', 'crispy-theme' ),
		'description' => __( 'Four-card navigation for routing visitors to speaking, blog, newsletter, or about.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'navigation', 'routing', 'cards', 'homepage', 'cta' ],
		'content'     => '<!-- wp:group {"className":"audience-routing","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull audience-routing" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center","level":2,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--50)">' . esc_html__( 'Where would you like to go?', 'crispy-theme' ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"className":"routing-cards","columns":4} -->
	<div class="wp-block-columns routing-cards has-4-columns">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"routing-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-group routing-card" style="padding:var(--wp--preset--spacing--40)">
				<!-- wp:html -->
				<div class="routing-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
						<path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
						<line x1="12" y1="19" x2="12" y2="23"></line>
						<line x1="8" y1="23" x2="16" y2="23"></line>
					</svg>
				</div>
				<!-- /wp:html -->

				<!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Speaking', 'crispy-theme' ) . '</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Book Christopher for your next event. Keynotes on AI, analytics, and data-driven marketing.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/speaking/">' . esc_html__( 'View Speaking', 'crispy-theme' ) . '</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"routing-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-group routing-card" style="padding:var(--wp--preset--spacing--40)">
				<!-- wp:html -->
				<div class="routing-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
						<path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
					</svg>
				</div>
				<!-- /wp:html -->

				<!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Blog', 'crispy-theme' ) . '</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Read insights on AI, marketing, data science, and technology. Code examples included.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/blog/">' . esc_html__( 'Read Blog', 'crispy-theme' ) . '</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"routing-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-group routing-card" style="padding:var(--wp--preset--spacing--40)">
				<!-- wp:html -->
				<div class="routing-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
						<polyline points="22,6 12,13 2,6"></polyline>
					</svg>
				</div>
				<!-- /wp:html -->

				<!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'Newsletter', 'crispy-theme' ) . '</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Join 294,000+ subscribers. Weekly insights on AI, marketing, and analytics.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.christopherspenn.com/newsletter" target="_blank" rel="noopener">' . esc_html__( 'Subscribe', 'crispy-theme' ) . '</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"routing-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-group routing-card" style="padding:var(--wp--preset--spacing--40)">
				<!-- wp:html -->
				<div class="routing-card-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
				</div>
				<!-- /wp:html -->

				<!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--20)">' . esc_html__( 'About', 'crispy-theme' ) . '</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--30)">' . esc_html__( 'Learn more about Christopher Penn, his background, and credentials.', 'crispy-theme' ) . '</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/about/">' . esc_html__( 'About Me', 'crispy-theme' ) . '</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
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
