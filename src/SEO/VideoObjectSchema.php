<?php
/**
 * VideoObject Schema builder.
 *
 * Builds a Schema.org VideoObject JSON-LD block for the keynote
 * demo reel. Returns null when no video URL is configured.
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * VideoObject Schema builder.
 */
class VideoObjectSchema {

	/**
	 * Settings reader.
	 *
	 * @var SchemaSettings
	 */
	private SchemaSettings $settings;

	/**
	 * Constructor.
	 *
	 * @param SchemaSettings $settings Schema settings reader.
	 */
	public function __construct( SchemaSettings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Build the VideoObject schema block.
	 *
	 * Returns null when the video URL is not configured — this is intentional;
	 * emitting an empty VideoObject would be invalid Schema.org markup.
	 *
	 * @return array<string, mixed>|null Schema array, or null if video URL is missing.
	 */
	public function build(): ?array {
		$video_url = $this->settings->get_video_url();

		if ( '' === $video_url ) {
			return null;
		}

		$data = [
			'@context'   => 'https://schema.org',
			'@type'      => 'VideoObject',
			'name'       => $this->settings->get_video_name(),
			'contentUrl' => $video_url,
			'embedUrl'   => $video_url,
		];

		// Add optional fields only when configured.
		$this->add_if_set( $data, 'description', $this->settings->get_video_description() );
		$this->add_if_set( $data, 'thumbnailUrl', $this->settings->get_video_thumbnail() );
		$this->add_if_set( $data, 'uploadDate', $this->settings->get_video_upload_date() );
		$this->add_if_set( $data, 'duration', $this->settings->get_video_duration() );

		return $data;
	}

	/**
	 * Add a key/value pair to an array only when the value is non-empty.
	 *
	 * @param array<string, mixed> $data  The array to mutate.
	 * @param string               $key   Schema.org property name.
	 * @param string               $value Value to add; skipped when empty string.
	 * @return void
	 */
	private function add_if_set( array &$data, string $key, string $value ): void {
		if ( '' !== $value ) {
			$data[ $key ] = $value;
		}
	}
}
