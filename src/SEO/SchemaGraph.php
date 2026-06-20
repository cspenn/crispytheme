<?php
/**
 * Schema Graph orchestrator.
 *
 * Composition root for Schema.org JSON-LD output. Registers the wp_head
 * action, builds all applicable schema blocks for the current request,
 * and emits them as <script type="application/ld+json"> tags.
 *
 * Context rules:
 * - Person + Organization: all front-end views
 * - VideoObject + Speakable: front page only
 *
 * @package CrispyTheme\SEO
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\SEO;

/**
 * Schema Graph orchestrator.
 */
class SchemaGraph {

	/**
	 * Person schema builder.
	 *
	 * @var PersonSchema
	 */
	private PersonSchema $person;

	/**
	 * Organization schema builder.
	 *
	 * @var OrganizationSchema
	 */
	private OrganizationSchema $organization;

	/**
	 * VideoObject schema builder.
	 *
	 * @var VideoObjectSchema
	 */
	private VideoObjectSchema $video;

	/**
	 * Speakable schema builder.
	 *
	 * @var SpeakableSchema
	 */
	private SpeakableSchema $speakable;

	/**
	 * Constructor. Builds all builder dependencies from a shared SchemaSettings.
	 */
	public function __construct() {
		$settings           = new SchemaSettings();
		$this->person       = new PersonSchema( $settings );
		$this->organization = new OrganizationSchema( $settings );
		$this->video        = new VideoObjectSchema( $settings );
		$this->speakable    = new SpeakableSchema( $settings );
	}

	/**
	 * Register the wp_head action hook.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'render' ], 5 );
	}

	/**
	 * Build and echo all applicable schema blocks.
	 *
	 * Does nothing in the admin context to avoid polluting admin pages.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( is_admin() ) {
			return;
		}

		// Person and Organization appear on all front-end views.
		$builders = [ $this->person, $this->organization ];

		// VideoObject and Speakable appear only on the front page.
		if ( is_front_page() ) {
			$builders[] = $this->video;
			$builders[] = $this->speakable;
		}

		foreach ( $builders as $builder ) {
			$data = $builder->build();

			if ( null === $data ) {
				continue;
			}

			// JSON_HEX_TAG and JSON_HEX_AMP escape <, > and & so the payload cannot
			// break out of the <script> element (script-context safe).
			$json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP );

			if ( false === $json ) {
				continue;
			}

			// JSON-LD has no context-appropriate WP escaping function; the JSON is made
			// script-safe by the hex flags above, so it is emitted directly.
			echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
