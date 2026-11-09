<?php
/**
 * Homepage Video Showcase (Demo Reel) Pattern.
 *
 * White-labeled demo reel on a dark navy surface. Vimeo is the default
 * (no competitor end-cards); YouTube is supported as a privacy-enhanced
 * fallback. Anchored at #demo-reel for the hero "Watch Demo Reel" CTA.
 * Content is configurable via Theme Options.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

// Check if video showcase is enabled.
$video_enabled = get_option( 'crispytheme_video_showcase_enabled', false );

if ( ! $video_enabled ) {
	return;
}

// Get configurable options with defaults.
$video_url         = get_option( 'crispytheme_video_showcase_url', '' );
$video_heading     = get_option( 'crispytheme_video_showcase_heading', 'See Christopher in Action' );
$video_description = get_option( 'crispytheme_video_showcase_description', 'Watch a recent keynote to experience the energy, insights, and practical takeaways audiences can expect.' );

// Don't register if no video URL provided.
if ( empty( $video_url ) ) {
	return;
}

// Build a white-labeled embed URL. Prefer Vimeo (no competitor end-cards).
$embed_url = '';

if ( preg_match( '#(?:vimeo\.com/(?:video/)?|player\.vimeo\.com/video/)(\d+)#i', $video_url, $matches ) ) {
	// Vimeo: hide title/byline/portrait, do-not-track. White-label requires a Vimeo Pro account.
	$embed_url = 'https://player.vimeo.com/video/' . $matches[1] . '?title=0&byline=0&portrait=0&dnt=1';
} elseif ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches ) ) {
	// YouTube fallback: nocookie host, reduced branding, related limited to same channel.
	$embed_url = 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?rel=0&modestbranding=1';
}

// Don't register if we couldn't build an embed URL.
if ( '' === $embed_url ) {
	return;
}

register_block_pattern(
	'crispytheme/homepage-video-showcase',
	[
		'title'       => __( 'Homepage Demo Reel', 'crispy-theme' ),
		'description' => __( 'White-labeled demo reel (Vimeo/YouTube) on a dark navy surface.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'video', 'vimeo', 'youtube', 'demo', 'reel', 'showcase', 'speaking', 'keynote' ],
		'content'     => '<!-- wp:group {"tagName":"section","anchor":"demo-reel","align":"full","className":"video-showcase","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"surface-dark","layout":{"type":"constrained","contentSize":"900px"}} -->
<section id="demo-reel" class="wp-block-group alignfull video-showcase has-surface-dark-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}},"typography":{"fontWeight":"700"}},"textColor":"on-dark"} -->
	<h2 class="wp-block-heading has-text-align-center has-on-dark-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700">' . esc_html( $video_heading ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"textColor":"on-dark-secondary"} -->
	<p class="has-text-align-center has-on-dark-secondary-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--50)">' . esc_html( $video_description ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"video-showcase-embed","layout":{"type":"default"}} -->
	<div class="wp-block-group video-showcase-embed" style="position:relative;width:100%;padding-bottom:56.25%;background:#000;">
		<!-- wp:html -->
		<iframe src="' . esc_url( $embed_url ) . '" title="' . esc_attr__( 'Featured Speaking Demo Reel', 'crispy-theme' ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;"></iframe>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->',
	]
);
