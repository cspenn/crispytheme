<?php
/**
 * Theme Assets class.
 *
 * Handles enqueueing of scripts and styles for both frontend and editor.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Theme;

/**
 * Theme Assets class.
 */
class Assets {

	/**
	 * Initialize asset loading.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_head', [ $this, 'add_dark_mode_script' ], 1 );
	}

	/**
	 * Enqueue frontend styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		$version = CRISPY_THEME_VERSION;

		// Enqueue main theme stylesheet.
		wp_enqueue_style(
			'crispy-theme-style',
			CRISPY_THEME_URI . '/style.css',
			[],
			$version
		);

		// Enqueue header and navigation styles.
		wp_enqueue_style(
			'crispy-theme-header',
			CRISPY_THEME_URI . '/assets/css/header.css',
			[ 'crispy-theme-style' ],
			$version
		);

		// Enqueue GitHub Markdown CSS.
		wp_enqueue_style(
			'crispy-theme-markdown',
			CRISPY_THEME_URI . '/assets/css/github-markdown.css',
			[],
			$version
		);

		// Enqueue dark mode markdown CSS.
		wp_enqueue_style(
			'crispy-theme-markdown-dark',
			CRISPY_THEME_URI . '/assets/css/github-markdown-dark.css',
			[ 'crispy-theme-markdown' ],
			$version
		);

		// Enqueue pattern styles.
		wp_enqueue_style(
			'crispy-theme-patterns',
			CRISPY_THEME_URI . '/assets/css/patterns.css',
			[ 'crispy-theme-style' ],
			$version
		);

		// Enqueue newsletter styles.
		wp_enqueue_style(
			'crispy-theme-newsletter',
			CRISPY_THEME_URI . '/assets/css/newsletter.css',
			[ 'crispy-theme-style' ],
			$version
		);

		// Enqueue code showcase styles.
		wp_enqueue_style(
			'crispy-theme-code-showcase',
			CRISPY_THEME_URI . '/assets/css/code-showcase.css',
			[ 'crispy-theme-style' ],
			$version
		);

		// Conditionally load Prism.js only when needed.
		if ( $this->should_load_prism() ) {
			$this->enqueue_prism_assets();
		}

		// Enqueue dark mode toggle script.
		wp_enqueue_script(
			'crispy-theme-dark-mode',
			CRISPY_THEME_URI . '/build/dark-mode-toggle.js',
			[],
			$version,
			true
		);

		// Pass dark mode settings to JavaScript.
		wp_localize_script(
			'crispy-theme-dark-mode',
			'crispyThemeDarkMode',
			[
				'storageKey'  => 'crispy-theme-dark-mode',
				'defaultMode' => 'auto',
			]
		);
	}

	/**
	 * Enqueue editor styles and scripts.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {
		$version = CRISPY_THEME_VERSION;

		// Add editor stylesheet.
		add_editor_style( 'assets/css/editor.css' );

		// Enqueue GitHub Markdown CSS for preview.
		wp_enqueue_style(
			'crispy-theme-markdown-editor',
			CRISPY_THEME_URI . '/assets/css/github-markdown.css',
			[],
			$version
		);
	}

	/**
	 * Enqueue admin-specific assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on post edit screens.
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$version = CRISPY_THEME_VERSION;

		// Enqueue admin preview styles.
		wp_enqueue_style(
			'crispy-theme-admin-preview',
			CRISPY_THEME_URI . '/assets/css/admin-preview.css',
			[],
			$version
		);

		// Enqueue GitHub Markdown CSS for preview.
		wp_enqueue_style(
			'crispy-theme-markdown-admin',
			CRISPY_THEME_URI . '/assets/css/github-markdown.css',
			[],
			$version
		);

		// Enqueue admin preview toggle script.
		wp_enqueue_script(
			'crispy-theme-admin-preview',
			CRISPY_THEME_URI . '/build/admin-preview.js',
			[ 'jquery' ],
			$version,
			true
		);

		// Pass AJAX URL and nonce to JavaScript.
		wp_localize_script(
			'crispy-theme-admin-preview',
			'crispyThemeAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'crispy_theme_preview' ),
			]
		);
	}

	/**
	 * Add inline script for dark mode to prevent FOUC.
	 *
	 * This runs in the head to set the dark mode class before page render.
	 *
	 * @return void
	 */
	public function add_dark_mode_script(): void {
		?>
		<script>
			(function() {
				const storageKey = 'crispy-theme-dark-mode';
				const stored = localStorage.getItem(storageKey);
				const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

				let isDark = false;
				if (stored === 'dark') {
					isDark = true;
				} else if (stored === 'light') {
					isDark = false;
				} else {
					isDark = prefersDark;
				}

				if (isDark) {
					document.documentElement.classList.add('dark-mode');
				}
			})();
		</script>
		<?php
	}

	/**
	 * Enqueue Prism.js assets for syntax highlighting.
	 *
	 * @return void
	 */
	private function enqueue_prism_assets(): void {
		$version = CRISPY_THEME_VERSION;

		// Enqueue Prism CSS theme.
		wp_enqueue_style(
			'crispy-theme-prism',
			CRISPY_THEME_URI . '/assets/css/prism-theme.css',
			[],
			$version
		);

		// Enqueue Prism JS.
		wp_enqueue_script(
			'crispy-theme-prism',
			CRISPY_THEME_URI . '/build/prism-clipboard.js',
			[],
			$version,
			true
		);
	}

	/**
	 * Determine if Prism.js should be loaded on the current page.
	 *
	 * @return bool True if Prism should be loaded.
	 */
	private function should_load_prism(): bool {
		// Always load on singular content.
		if ( is_singular() ) {
			return true;
		}

		/**
		 * Filter whether to load Prism.js.
		 *
		 * @param bool $load Whether to load Prism.
		 */
		return apply_filters( 'crispytheme_load_prism', false );
	}
}
