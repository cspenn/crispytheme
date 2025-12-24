<?php
/**
 * Dark Mode Toggle class.
 *
 * Handles dark mode functionality including state management and toggle rendering.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\DarkMode;

/**
 * Dark Mode Toggle class.
 */
class Toggle {

	/**
	 * Local storage key for dark mode preference.
	 */
	public const STORAGE_KEY = 'crispy-theme-dark-mode';

	/**
	 * Dark mode value: auto (follow system).
	 */
	public const MODE_AUTO = 'auto';

	/**
	 * Dark mode value: light.
	 */
	public const MODE_LIGHT = 'light';

	/**
	 * Dark mode value: dark.
	 */
	public const MODE_DARK = 'dark';

	/**
	 * Initialize dark mode functionality.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add body class for dark mode.
		add_filter( 'body_class', [ $this, 'add_body_class' ] );

		// Register the dark mode toggle block.
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Add dark mode body class.
	 *
	 * Note: The actual class is added via JavaScript to prevent FOUC.
	 * This filter adds a marker class for CSS targeting.
	 *
	 * @param string[] $classes The body classes.
	 * @return string[] The modified body classes.
	 */
	public function add_body_class( array $classes ): array {
		$classes[] = 'crispy-theme-dark-mode-enabled';

		return $classes;
	}

	/**
	 * Register the dark mode toggle block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			'crispytheme/dark-mode-toggle',
			[
				'render_callback' => [ $this, 'render_toggle' ],
				'attributes'      => [
					'showLabel' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'className' => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);
	}

	/**
	 * Render the dark mode toggle button.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string The toggle HTML.
	 */
	public function render_toggle( array $attributes ): string {
		$show_label = $attributes['showLabel'] ?? false;
		$class_name = $attributes['className'] ?? '';

		$classes = [ 'crispy-dark-mode-toggle' ];
		if ( ! empty( $class_name ) ) {
			$classes[] = $class_name;
		}

		ob_start();
		?>
		<button
			type="button"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			aria-label="<?php esc_attr_e( 'Toggle dark mode', 'crispy-theme' ); ?>"
			aria-pressed="false"
			data-dark-mode-toggle
		>
			<span class="crispy-dark-mode-toggle__icon crispy-dark-mode-toggle__icon--light" aria-hidden="true">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded and safe.
				echo $this->get_sun_icon();
				?>
			</span>
			<span class="crispy-dark-mode-toggle__icon crispy-dark-mode-toggle__icon--dark" aria-hidden="true">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded and safe.
				echo $this->get_moon_icon();
				?>
			</span>
			<?php if ( $show_label ) : ?>
				<span class="crispy-dark-mode-toggle__label crispy-dark-mode-toggle__label--light">
					<?php esc_html_e( 'Light', 'crispy-theme' ); ?>
				</span>
				<span class="crispy-dark-mode-toggle__label crispy-dark-mode-toggle__label--dark">
					<?php esc_html_e( 'Dark', 'crispy-theme' ); ?>
				</span>
			<?php endif; ?>
		</button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the sun icon SVG.
	 *
	 * @return string The SVG markup.
	 */
	private function get_sun_icon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';
	}

	/**
	 * Get the moon icon SVG.
	 *
	 * @return string The SVG markup.
	 */
	private function get_moon_icon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
	}

	/**
	 * Get CSS for dark mode toggle.
	 *
	 * @return string The CSS.
	 */
	public static function get_toggle_styles(): string {
		return '
            .crispy-dark-mode-toggle {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem;
                background: transparent;
                border: none;
                cursor: pointer;
                color: inherit;
                border-radius: 0.375rem;
                transition: background-color 0.2s;
            }

            .crispy-dark-mode-toggle:hover {
                background-color: rgba(0, 0, 0, 0.05);
            }

            .dark-mode .crispy-dark-mode-toggle:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }

            .crispy-dark-mode-toggle__icon--dark,
            .crispy-dark-mode-toggle__label--dark {
                display: none;
            }

            .dark-mode .crispy-dark-mode-toggle__icon--light,
            .dark-mode .crispy-dark-mode-toggle__label--light {
                display: none;
            }

            .dark-mode .crispy-dark-mode-toggle__icon--dark,
            .dark-mode .crispy-dark-mode-toggle__label--dark {
                display: inline-flex;
            }
        ';
	}
}
