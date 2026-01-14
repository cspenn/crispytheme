<?php
/**
 * Homepage Video Showcase Pattern.
 *
 * Prominent video section with heading and description.
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

// Extract YouTube video ID from URL.
$video_id = '';
if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches ) ) {
	$video_id = $matches[1];
}

// Don't register if we couldn't extract a video ID.
if ( empty( $video_id ) ) {
	return;
}

$embed_url = 'https://www.youtube.com/embed/' . $video_id . '?rel=0';

register_block_pattern(
	'crispytheme/homepage-video-showcase',
	[
		'title'       => __( 'Homepage Video Showcase', 'crispy-theme' ),
		'description' => __( 'Prominent video section to showcase speaking samples.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-homepage', 'crispytheme' ],
		'keywords'    => [ 'video', 'youtube', 'showcase', 'speaking', 'keynote' ],
		'content'     => '<!-- wp:group {"className":"video-showcase","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"contrast","layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group video-showcase has-contrast-background-color has-background" style="padding:var(--wp--preset--spacing--70) var(--wp--preset--spacing--50)">
	<!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}},"typography":{"fontWeight":"700"}},"textColor":"background"} -->
	<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700">' . esc_html( $video_heading ) . '</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"textColor":"foreground-secondary"} -->
	<p class="has-text-align-center has-foreground-secondary-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--50)">' . esc_html( $video_description ) . '</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"video-showcase-embed","style":{"dimensions":{"minHeight":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group video-showcase-embed" style="position:relative;width:100%;padding-bottom:56.25%;background:#000;">
		<!-- wp:html -->
		<iframe src="' . esc_url( $embed_url ) . '" title="' . esc_attr__( 'Featured Speaking Video', 'crispy-theme' ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;"></iframe>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->',
	]
);
