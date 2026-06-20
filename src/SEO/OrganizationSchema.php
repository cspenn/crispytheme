<?php
/**
 * Organization Schema builder.
 *
 * Builds a Schema.org Organization JSON-LD block representing
 * Christopher Penn's speaking/consulting entity.
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * Organization Schema builder.
 */
class OrganizationSchema {

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
	 * Build the Organization schema block.
	 *
	 * Returns null when the organization name option is empty.
	 *
	 * @return array<string, mixed>|null Schema array, or null if essential data is missing.
	 */
	public function build(): ?array {
		$name = $this->settings->get_org_name();

		if ( '' === $name ) {
			return null;
		}

		$booking_url = $this->settings->get_booking_url();

		$contact_point = [
			'@type'       => 'ContactPoint',
			'contactType' => 'Booking Inquiry',
			'url'         => $booking_url,
		];

		// Add email to contactPoint only when configured.
		$email = $this->settings->get_booking_email();
		if ( '' !== $email ) {
			$contact_point['email'] = $email;
		}

		$data = [
			'@context'     => 'https://schema.org',
			'@type'        => 'Organization',
			'name'         => $name,
			'url'          => $this->settings->get_home_url(),
			'contactPoint' => $contact_point,
		];

		// Add logo only when configured.
		$logo = $this->settings->get_org_logo();
		if ( '' !== $logo ) {
			$data['logo'] = $logo;
		}

		return $data;
	}
}
