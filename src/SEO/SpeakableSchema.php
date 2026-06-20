<?php
/**
 * Speakable Schema builder.
 *
 * Builds a Schema.org WebPage + SpeakableSpecification JSON-LD block
 * allowing voice assistants and AI to identify speakable content.
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * Speakable Schema builder.
 */
class SpeakableSchema {

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
	 * Build the Speakable WebPage schema block.
	 *
	 * Emits a WebPage with a SpeakableSpecification targeting
	 * `.speakable-summary` CSS selector.
	 *
	 * @return array<string, mixed> Schema array.
	 */
	public function build(): array {
		return [
			'@context'  => 'https://schema.org',
			'@type'     => 'WebPage',
			'url'       => $this->settings->get_current_url(),
			'speakable' => [
				'@type'       => 'SpeakableSpecification',
				'cssSelector' => [ '.speakable-summary' ],
			],
		];
	}
}
