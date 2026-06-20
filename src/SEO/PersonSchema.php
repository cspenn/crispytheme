<?php
/**
 * Person Schema builder.
 *
 * Builds a Schema.org Person JSON-LD block for Christopher Penn.
 * Hard-coded brand values (knowsAbout, hasCredential, affiliation)
 * are baked in; all user-configurable values are read via SchemaSettings.
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * Person Schema builder.
 */
class PersonSchema {

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
	 * Build the Person schema block.
	 *
	 * Returns null when the person name option is empty, since @type Person
	 * without a name is not meaningful.
	 *
	 * @return array<string, mixed>|null Schema array, or null if essential data is missing.
	 */
	public function build(): ?array {
		$name = $this->settings->get_person_name();

		if ( '' === $name ) {
			return null;
		}

		$data = [
			'@context'      => 'https://schema.org',
			'@type'         => 'Person',
			'name'          => $name,
			'url'           => $this->settings->get_home_url(),
			'jobTitle'      => $this->settings->get_person_job_title(),
			'description'   => $this->settings->get_person_description(),
			'knowsAbout'    => [
				'Artificial Intelligence',
				'Marketing Analytics',
				'Generative AI',
				'Data Science',
			],
			'hasCredential' => [
				[
					'@type' => 'EducationalOccupationalCredential',
					'name'  => 'IBM Champion',
				],
			],
			'affiliation'   => [
				'@type' => 'Organization',
				'name'  => 'Trust Insights',
				'url'   => 'https://www.trustinsights.ai',
			],
		];

		// Add optional image only when configured.
		$image = $this->settings->get_person_image();
		if ( '' !== $image ) {
			$data['image'] = $image;
		}

		// Add sameAs only when at least one URL is configured.
		$same_as = $this->settings->get_same_as();
		if ( [] !== $same_as ) {
			$data['sameAs'] = $same_as;
		}

		return $data;
	}
}
