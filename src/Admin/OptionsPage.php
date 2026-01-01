<?php
/**
 * Admin Options Page class.
 *
 * Handles the theme options page under Appearance menu.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Admin;

use CrispyTheme\Content\RSSFilter;

/**
 * Admin Options Page class.
 */
class OptionsPage {

	/**
	 * The options page slug.
	 */
	private const PAGE_SLUG = 'crispytheme-options';

	/**
	 * The option group name.
	 */
	private const OPTION_GROUP = 'crispytheme_options';

	/**
	 * Initialize the options page.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add the options page to the Appearance menu.
	 *
	 * @return void
	 */
	public function add_options_page(): void {
		add_theme_page(
			__( 'CrispyTheme Options', 'crispy-theme' ),
			__( 'Theme Options', 'crispy-theme' ),
			'edit_theme_options',
			self::PAGE_SLUG,
			[ $this, 'render_options_page' ]
		);
	}

	/**
	 * Register theme settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Register RSS settings section.
		add_settings_section(
			'crispytheme_rss_section',
			__( 'RSS Feed Settings', 'crispy-theme' ),
			[ $this, 'render_rss_section' ],
			self::PAGE_SLUG
		);

		// RSS content mode setting.
		register_setting(
			self::OPTION_GROUP,
			RSSFilter::OPTION_KEY,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_rss_mode' ],
				'default'           => RSSFilter::MODE_FULL,
			]
		);

		add_settings_field(
			'crispytheme_rss_content',
			__( 'Feed Content', 'crispy-theme' ),
			[ $this, 'render_rss_content_field' ],
			self::PAGE_SLUG,
			'crispytheme_rss_section'
		);

		// Register archive settings section.
		add_settings_section(
			'crispytheme_archive_section',
			__( 'Archive Settings', 'crispy-theme' ),
			[ $this, 'render_archive_section' ],
			self::PAGE_SLUG
		);

		// Archive display mode setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_archive_display',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_archive_display' ],
				'default'           => 'excerpt',
			]
		);

		add_settings_field(
			'crispytheme_archive_display',
			__( 'Archive Display', 'crispy-theme' ),
			[ $this, 'render_archive_display_field' ],
			self::PAGE_SLUG,
			'crispytheme_archive_section'
		);

		// Register markdown settings section.
		add_settings_section(
			'crispytheme_markdown_section',
			__( 'Markdown Settings', 'crispy-theme' ),
			[ $this, 'render_markdown_section' ],
			self::PAGE_SLUG
		);

		// Parser type setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_parser_type',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_parser_type' ],
				'default'           => 'extra',
			]
		);

		add_settings_field(
			'crispytheme_parser_type',
			__( 'Markdown Parser', 'crispy-theme' ),
			[ $this, 'render_parser_type_field' ],
			self::PAGE_SLUG,
			'crispytheme_markdown_section'
		);

		// Register layout settings section.
		add_settings_section(
			'crispytheme_layout_section',
			__( 'Layout Settings', 'crispy-theme' ),
			[ $this, 'render_layout_section' ],
			self::PAGE_SLUG
		);

		// Sidebar width setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_sidebar_width',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_sidebar_width' ],
				'default'           => '350px',
			]
		);

		add_settings_field(
			'crispytheme_sidebar_width',
			__( 'Sidebar Width', 'crispy-theme' ),
			[ $this, 'render_sidebar_width_field' ],
			self::PAGE_SLUG,
			'crispytheme_layout_section'
		);

		// Max container width setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_max_width',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_max_width' ],
				'default'           => '1800px',
			]
		);

		add_settings_field(
			'crispytheme_max_width',
			__( 'Max Container Width', 'crispy-theme' ),
			[ $this, 'render_max_width_field' ],
			self::PAGE_SLUG,
			'crispytheme_layout_section'
		);
	}

	/**
	 * Render the options page.
	 *
	 * @return void
	 */
	public function render_options_page(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the RSS section description.
	 *
	 * @return void
	 */
	public function render_rss_section(): void {
		echo '<p>' . esc_html__( 'Configure how content appears in RSS feeds.', 'crispy-theme' ) . '</p>';
	}

	/**
	 * Render the archive section description.
	 *
	 * @return void
	 */
	public function render_archive_section(): void {
		echo '<p>' . esc_html__( 'Configure how posts appear on archive pages.', 'crispy-theme' ) . '</p>';
	}

	/**
	 * Render the markdown section description.
	 *
	 * @return void
	 */
	public function render_markdown_section(): void {
		echo '<p>' . esc_html__( 'Configure markdown parsing options.', 'crispy-theme' ) . '</p>';
	}

	/**
	 * Render the RSS content field.
	 *
	 * @return void
	 */
	public function render_rss_content_field(): void {
		$value = get_option( RSSFilter::OPTION_KEY, RSSFilter::MODE_FULL );
		$modes = RSSFilter::get_available_modes();

		foreach ( $modes as $mode => $label ) {
			?>
			<label style="display: block; margin-bottom: 8px;">
				<input type="radio"
						name="<?php echo esc_attr( RSSFilter::OPTION_KEY ); ?>"
						value="<?php echo esc_attr( $mode ); ?>"
						<?php checked( $value, $mode ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Choose whether to show full post content or just excerpts in RSS feeds.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the archive display field.
	 *
	 * @return void
	 */
	public function render_archive_display_field(): void {
		$value   = get_option( 'crispytheme_archive_display', 'excerpt' );
		$options = [
			'excerpt' => __( 'Excerpt Only', 'crispy-theme' ),
			'full'    => __( 'Full Content', 'crispy-theme' ),
		];

		foreach ( $options as $option => $label ) {
			?>
			<label style="display: block; margin-bottom: 8px;">
				<input type="radio"
						name="crispytheme_archive_display"
						value="<?php echo esc_attr( $option ); ?>"
						<?php checked( $value, $option ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Choose how posts are displayed on category, tag, and date archive pages.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the parser type field.
	 *
	 * @return void
	 */
	public function render_parser_type_field(): void {
		$value   = get_option( 'crispytheme_parser_type', 'extra' );
		$options = [
			'extra' => __( 'Parsedown Extra (Recommended)', 'crispy-theme' ),
			'basic' => __( 'Basic Parsedown', 'crispy-theme' ),
		];

		foreach ( $options as $option => $label ) {
			?>
			<label style="display: block; margin-bottom: 8px;">
				<input type="radio"
						name="crispytheme_parser_type"
						value="<?php echo esc_attr( $option ); ?>"
						<?php checked( $value, $option ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
			<?php
		}
		?>
		<p class="description">
			<?php esc_html_e( 'Parsedown Extra supports tables, footnotes, and fenced code blocks. Basic Parsedown is simpler and faster.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize the RSS mode option.
	 *
	 * @param mixed $value The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_rss_mode( $value ): string {
		$valid = [ RSSFilter::MODE_FULL, RSSFilter::MODE_EXCERPT ];

		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}

		return RSSFilter::MODE_FULL;
	}

	/**
	 * Sanitize the archive display option.
	 *
	 * @param mixed $value The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_archive_display( $value ): string {
		$valid = [ 'excerpt', 'full' ];

		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}

		return 'excerpt';
	}

	/**
	 * Sanitize the parser type option.
	 *
	 * @param mixed $value The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_parser_type( $value ): string {
		$valid = [ 'basic', 'extra' ];

		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}

		return 'extra';
	}

	/**
	 * Render the layout section description.
	 *
	 * @return void
	 */
	public function render_layout_section(): void {
		echo '<p>' . esc_html__( 'Configure the responsive layout for content and sidebar.', 'crispy-theme' ) . '</p>';
	}

	/**
	 * Render the sidebar width field.
	 *
	 * @return void
	 */
	public function render_sidebar_width_field(): void {
		$value   = get_option( 'crispytheme_sidebar_width', '350px' );
		$options = [
			'300px' => __( '300px (Narrow)', 'crispy-theme' ),
			'350px' => __( '350px (Default)', 'crispy-theme' ),
			'400px' => __( '400px (Wide)', 'crispy-theme' ),
		];
		?>
		<select name="crispytheme_sidebar_width">
			<?php foreach ( $options as $option => $label ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'The fixed width of the sidebar. Content area expands to fill remaining space.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the max width field.
	 *
	 * @return void
	 */
	public function render_max_width_field(): void {
		$value   = get_option( 'crispytheme_max_width', '1800px' );
		$options = [
			'1400px' => __( '1400px (Compact)', 'crispy-theme' ),
			'1600px' => __( '1600px (Medium)', 'crispy-theme' ),
			'1800px' => __( '1800px (Default)', 'crispy-theme' ),
			'2000px' => __( '2000px (Wide)', 'crispy-theme' ),
		];
		?>
		<select name="crispytheme_max_width">
			<?php foreach ( $options as $option => $label ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Maximum width of the main content container before side margins take over.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize the sidebar width option.
	 *
	 * @param mixed $value The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_sidebar_width( $value ): string {
		$valid = [ '300px', '350px', '400px' ];

		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}

		return '350px';
	}

	/**
	 * Sanitize the max width option.
	 *
	 * @param mixed $value The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_max_width( $value ): string {
		$valid = [ '1400px', '1600px', '1800px', '2000px' ];

		if ( in_array( $value, $valid, true ) ) {
			return $value;
		}

		return '1800px';
	}

	/**
	 * Get the page slug.
	 *
	 * @return string The page slug.
	 */
	public static function get_page_slug(): string {
		return self::PAGE_SLUG;
	}
}
