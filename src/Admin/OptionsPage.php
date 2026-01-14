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

use CrispyTheme\Content\MarkdownDropdown;
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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Enqueue admin scripts for the options page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( 'appearance_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_media();

		$inline_script = "
			jQuery(document).ready(function($) {
				$('.crispytheme-media-upload').on('click', function(e) {
					e.preventDefault();
					var button = $(this);
					var targetId = button.data('target');
					var mediaUploader = wp.media({
						title: '" . esc_js( __( 'Select Image', 'crispy-theme' ) ) . "',
						button: { text: '" . esc_js( __( 'Use This Image', 'crispy-theme' ) ) . "' },
						multiple: false
					});
					mediaUploader.on('select', function() {
						var attachment = mediaUploader.state().get('selection').first().toJSON();
						$('#' + targetId).val(attachment.url);
					});
					mediaUploader.open();
				});
			});
		";

		// Register script with proper dependencies to ensure wp.media is loaded.
		wp_register_script(
			'crispytheme-admin-options',
			false,
			[ 'jquery', 'media-editor' ],
			'1.0.0',
			true
		);

		wp_enqueue_script( 'crispytheme-admin-options' );
		wp_add_inline_script( 'crispytheme-admin-options', $inline_script );
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

		// Register homepage settings section.
		add_settings_section(
			'crispytheme_homepage_section',
			__( 'Homepage Settings', 'crispy-theme' ),
			[ $this, 'render_homepage_section' ],
			self::PAGE_SLUG
		);

		// Hero image setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_hero_image',
			[
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			]
		);

		add_settings_field(
			'crispytheme_hero_image',
			__( 'Hero Background Image', 'crispy-theme' ),
			[ $this, 'render_hero_image_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Hero eyebrow setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_hero_eyebrow',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'IBM Champion · 8× Author · 294,000+ Newsletter Subscribers',
			]
		);

		add_settings_field(
			'crispytheme_hero_eyebrow',
			__( 'Hero Eyebrow Text', 'crispy-theme' ),
			[ $this, 'render_hero_eyebrow_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Hero headline setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_hero_headline',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Data-Driven Marketing Strategist & AI Implementation Expert',
			]
		);

		add_settings_field(
			'crispytheme_hero_headline',
			__( 'Hero Headline', 'crispy-theme' ),
			[ $this, 'render_hero_headline_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Hero subheadline setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_hero_subheadline',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'Practitioner-scholar approach to AI, marketing analytics, and data science. No hype, just results.',
			]
		);

		add_settings_field(
			'crispytheme_hero_subheadline',
			__( 'Hero Subheadline', 'crispy-theme' ),
			[ $this, 'render_hero_subheadline_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Stats section.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_stat_events',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '500+',
			]
		);

		add_settings_field(
			'crispytheme_stat_events',
			__( 'Events Stat', 'crispy-theme' ),
			[ $this, 'render_stat_events_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		register_setting(
			self::OPTION_GROUP,
			'crispytheme_stat_countries',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '25',
			]
		);

		add_settings_field(
			'crispytheme_stat_countries',
			__( 'Countries Stat', 'crispy-theme' ),
			[ $this, 'render_stat_countries_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		register_setting(
			self::OPTION_GROUP,
			'crispytheme_stat_satisfaction',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '98%',
			]
		);

		add_settings_field(
			'crispytheme_stat_satisfaction',
			__( 'Satisfaction Stat', 'crispy-theme' ),
			[ $this, 'render_stat_satisfaction_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Video showcase enabled setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_video_showcase_enabled',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			]
		);

		add_settings_field(
			'crispytheme_video_showcase_enabled',
			__( 'Enable Video Showcase', 'crispy-theme' ),
			[ $this, 'render_video_enabled_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Video showcase URL setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_video_showcase_url',
			[
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			]
		);

		add_settings_field(
			'crispytheme_video_showcase_url',
			__( 'Video URL', 'crispy-theme' ),
			[ $this, 'render_video_url_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Video showcase heading setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_video_showcase_heading',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'See Christopher in Action',
			]
		);

		add_settings_field(
			'crispytheme_video_showcase_heading',
			__( 'Video Section Heading', 'crispy-theme' ),
			[ $this, 'render_video_heading_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Video showcase description setting.
		register_setting(
			self::OPTION_GROUP,
			'crispytheme_video_showcase_description',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => 'Watch a recent keynote to experience the energy, insights, and practical takeaways audiences can expect.',
			]
		);

		add_settings_field(
			'crispytheme_video_showcase_description',
			__( 'Video Section Description', 'crispy-theme' ),
			[ $this, 'render_video_description_field' ],
			self::PAGE_SLUG,
			'crispytheme_homepage_section'
		);

		// Client logos section.
		for ( $i = 1; $i <= 6; $i++ ) {
			register_setting(
				self::OPTION_GROUP,
				'crispytheme_client_logo_' . $i,
				[
					'type'              => 'string',
					'sanitize_callback' => 'esc_url_raw',
					'default'           => '',
				]
			);

			add_settings_field(
				'crispytheme_client_logo_' . $i,
				/* translators: %d: logo number */
				sprintf( __( 'Client Logo %d', 'crispy-theme' ), $i ),
				[ $this, 'render_client_logo_field' ],
				self::PAGE_SLUG,
				'crispytheme_homepage_section',
				[ 'logo_number' => $i ]
			);
		}

		// Register markdown analytics section (display only, no settings to register).
		add_settings_section(
			'crispytheme_analytics_section',
			__( 'Markdown Dropdown Analytics', 'crispy-theme' ),
			[ $this, 'render_analytics_section' ],
			self::PAGE_SLUG
		);

		// Register markdown shortcode replacements section.
		add_settings_section(
			'crispytheme_shortcodes_section',
			__( 'Markdown Shortcode Replacements', 'crispy-theme' ),
			[ $this, 'render_shortcodes_section' ],
			self::PAGE_SLUG
		);

		register_setting(
			self::OPTION_GROUP,
			'crispytheme_markdown_shortcodes',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_markdown_shortcodes' ],
				'default'           => '',
			]
		);

		add_settings_field(
			'crispytheme_markdown_shortcodes',
			__( 'Shortcode Definitions', 'crispy-theme' ),
			[ $this, 'render_shortcodes_field' ],
			self::PAGE_SLUG,
			'crispytheme_shortcodes_section'
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
	 * Render the homepage section description.
	 *
	 * @return void
	 */
	public function render_homepage_section(): void {
		echo '<p>' . esc_html__( 'Configure the homepage hero, stats, and trust signals.', 'crispy-theme' ) . '</p>';
	}

	/**
	 * Render the markdown analytics section.
	 *
	 * Displays aggregate stats and top posts by markdown dropdown usage.
	 *
	 * @return void
	 */
	public function render_analytics_section(): void {
		$stats     = MarkdownDropdown::get_analytics_stats();
		$top_posts = MarkdownDropdown::get_top_posts( 10 );

		?>
		<p><?php esc_html_e( 'Track usage of the "Copy page" and "View as Markdown" dropdown.', 'crispy-theme' ); ?></p>

		<div style="display: flex; gap: 20px; margin: 20px 0;">
			<div style="background: #f0f6fc; border: 1px solid #d0d7de; border-radius: 6px; padding: 20px; text-align: center; min-width: 120px;">
				<div style="font-size: 32px; font-weight: 600; color: #0969da;">
					<?php echo esc_html( number_format( $stats['total_copies'] ) ); ?>
				</div>
				<div style="font-size: 14px; color: #57606a; margin-top: 4px;">
					<?php esc_html_e( 'Total Copies', 'crispy-theme' ); ?>
				</div>
			</div>
			<div style="background: #f0f6fc; border: 1px solid #d0d7de; border-radius: 6px; padding: 20px; text-align: center; min-width: 120px;">
				<div style="font-size: 32px; font-weight: 600; color: #0969da;">
					<?php echo esc_html( number_format( $stats['total_views'] ) ); ?>
				</div>
				<div style="font-size: 14px; color: #57606a; margin-top: 4px;">
					<?php esc_html_e( 'Total Views', 'crispy-theme' ); ?>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $top_posts ) ) : ?>
			<h4 style="margin: 30px 0 10px;"><?php esc_html_e( 'Top Posts by Markdown Usage', 'crispy-theme' ); ?></h4>
			<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
				<thead>
					<tr>
						<th style="width: 50%;"><?php esc_html_e( 'Post Title', 'crispy-theme' ); ?></th>
						<th style="width: 15%; text-align: center;"><?php esc_html_e( 'Copies', 'crispy-theme' ); ?></th>
						<th style="width: 15%; text-align: center;"><?php esc_html_e( 'Views', 'crispy-theme' ); ?></th>
						<th style="width: 20%; text-align: center;"><?php esc_html_e( 'Total', 'crispy-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $top_posts as $post_data ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $post_data['ID'] ) ); ?>">
									<?php echo esc_html( $post_data['post_title'] ); ?>
								</a>
								<span style="color: #57606a; font-size: 12px; margin-left: 4px;">
									(<?php echo esc_html( $post_data['post_type'] ); ?>)
								</span>
							</td>
							<td style="text-align: center;"><?php echo esc_html( number_format( $post_data['copy_count'] ) ); ?></td>
							<td style="text-align: center;"><?php echo esc_html( number_format( $post_data['view_count'] ) ); ?></td>
							<td style="text-align: center; font-weight: 600;"><?php echo esc_html( number_format( $post_data['total_count'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p style="color: #57606a; font-style: italic;">
				<?php esc_html_e( 'No usage data yet. Stats will appear here once visitors use the markdown dropdown.', 'crispy-theme' ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $stats['last_updated'] ) : ?>
			<p style="color: #57606a; font-size: 12px; margin-top: 15px;">
				<?php
				printf(
					/* translators: %s: date and time */
					esc_html__( 'Last updated: %s', 'crispy-theme' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $stats['last_updated'] ) ) )
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the hero image field with media uploader.
	 *
	 * @return void
	 */
	public function render_hero_image_field(): void {
		$value = get_option( 'crispytheme_hero_image', '' );
		?>
		<div class="crispytheme-media-field">
			<input type="text"
					id="crispytheme_hero_image"
					name="crispytheme_hero_image"
					value="<?php echo esc_url( $value ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Enter image URL or use media library', 'crispy-theme' ); ?>">
			<button type="button" class="button crispytheme-media-upload" data-target="crispytheme_hero_image">
				<?php esc_html_e( 'Select Image', 'crispy-theme' ); ?>
			</button>
			<?php if ( $value ) : ?>
				<div style="margin-top: 10px;">
					<img src="<?php echo esc_url( $value ); ?>" style="max-width: 300px; height: auto;">
				</div>
			<?php endif; ?>
		</div>
		<p class="description">
			<?php esc_html_e( 'The background image for the homepage hero section. Recommended size: 1920x1080px.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the hero eyebrow field.
	 *
	 * @return void
	 */
	public function render_hero_eyebrow_field(): void {
		$value = get_option( 'crispytheme_hero_eyebrow', 'IBM Champion · 8× Author · 294,000+ Newsletter Subscribers' );
		?>
		<input type="text"
				name="crispytheme_hero_eyebrow"
				value="<?php echo esc_attr( $value ); ?>"
				class="large-text">
		<p class="description">
			<?php esc_html_e( 'Small text above the headline showing credentials. Use · to separate items.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the hero headline field.
	 *
	 * @return void
	 */
	public function render_hero_headline_field(): void {
		$value = get_option( 'crispytheme_hero_headline', 'Data-Driven Marketing Strategist & AI Implementation Expert' );
		?>
		<input type="text"
				name="crispytheme_hero_headline"
				value="<?php echo esc_attr( $value ); ?>"
				class="large-text">
		<p class="description">
			<?php esc_html_e( 'The main headline displayed in the hero section.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the hero subheadline field.
	 *
	 * @return void
	 */
	public function render_hero_subheadline_field(): void {
		$value = get_option( 'crispytheme_hero_subheadline', 'Practitioner-scholar approach to AI, marketing analytics, and data science. No hype, just results.' );
		?>
		<textarea name="crispytheme_hero_subheadline"
				rows="2"
				class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Supporting text below the headline.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the events stat field.
	 *
	 * @return void
	 */
	public function render_stat_events_field(): void {
		$value = get_option( 'crispytheme_stat_events', '500+' );
		?>
		<input type="text"
				name="crispytheme_stat_events"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text">
		<span class="description"><?php esc_html_e( 'e.g., 500+', 'crispy-theme' ); ?></span>
		<?php
	}

	/**
	 * Render the countries stat field.
	 *
	 * @return void
	 */
	public function render_stat_countries_field(): void {
		$value = get_option( 'crispytheme_stat_countries', '25' );
		?>
		<input type="text"
				name="crispytheme_stat_countries"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text">
		<span class="description"><?php esc_html_e( 'e.g., 25', 'crispy-theme' ); ?></span>
		<?php
	}

	/**
	 * Render the satisfaction stat field.
	 *
	 * @return void
	 */
	public function render_stat_satisfaction_field(): void {
		$value = get_option( 'crispytheme_stat_satisfaction', '98%' );
		?>
		<input type="text"
				name="crispytheme_stat_satisfaction"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text">
		<span class="description"><?php esc_html_e( 'e.g., 98%', 'crispy-theme' ); ?></span>
		<?php
	}

	/**
	 * Render the video enabled checkbox field.
	 *
	 * @return void
	 */
	public function render_video_enabled_field(): void {
		$value = get_option( 'crispytheme_video_showcase_enabled', false );
		?>
		<label>
			<input type="checkbox"
					name="crispytheme_video_showcase_enabled"
					value="1"
					<?php checked( $value, true ); ?>>
			<?php esc_html_e( 'Show video showcase section on homepage', 'crispy-theme' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Enable to display a prominent video section after the hero.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the video URL field.
	 *
	 * @return void
	 */
	public function render_video_url_field(): void {
		$value = get_option( 'crispytheme_video_showcase_url', '' );
		?>
		<input type="url"
				name="crispytheme_video_showcase_url"
				value="<?php echo esc_url( $value ); ?>"
				class="large-text"
				placeholder="https://www.youtube.com/watch?v=...">
		<p class="description">
			<?php esc_html_e( 'YouTube video URL. Supports youtube.com and youtu.be links.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the video heading field.
	 *
	 * @return void
	 */
	public function render_video_heading_field(): void {
		$value = get_option( 'crispytheme_video_showcase_heading', 'See Christopher in Action' );
		?>
		<input type="text"
				name="crispytheme_video_showcase_heading"
				value="<?php echo esc_attr( $value ); ?>"
				class="large-text">
		<p class="description">
			<?php esc_html_e( 'Heading displayed above the video.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the video description field.
	 *
	 * @return void
	 */
	public function render_video_description_field(): void {
		$value = get_option( 'crispytheme_video_showcase_description', 'Watch a recent keynote to experience the energy, insights, and practical takeaways audiences can expect.' );
		?>
		<textarea name="crispytheme_video_showcase_description"
				rows="2"
				class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Supporting text below the heading.', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Render a client logo field with media uploader.
	 *
	 * @param array<string, int> $args The field arguments including logo_number.
	 * @return void
	 */
	public function render_client_logo_field( array $args ): void {
		$logo_number = $args['logo_number'];
		$option_name = 'crispytheme_client_logo_' . $logo_number;
		$value       = get_option( $option_name, '' );

		$client_names = [
			1 => 'IBM',
			2 => 'Cisco',
			3 => 'T-Mobile',
			4 => "McDonald's",
			5 => 'GoDaddy',
			6 => 'AAA',
		];
		$client_name  = $client_names[ $logo_number ] ?? '';
		?>
		<div class="crispytheme-media-field">
			<input type="text"
					id="<?php echo esc_attr( $option_name ); ?>"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="<?php echo esc_url( $value ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Enter logo URL or use media library', 'crispy-theme' ); ?>">
			<button type="button" class="button crispytheme-media-upload" data-target="<?php echo esc_attr( $option_name ); ?>">
				<?php esc_html_e( 'Select Image', 'crispy-theme' ); ?>
			</button>
			<?php if ( $value ) : ?>
				<div style="margin-top: 10px;">
					<img src="<?php echo esc_url( $value ); ?>" style="max-width: 150px; max-height: 80px; height: auto; background: #f0f0f0; padding: 10px;">
				</div>
			<?php endif; ?>
		</div>
		<p class="description">
			<?php
			/* translators: %s: suggested client name */
			printf( esc_html__( 'Logo image URL. Suggested: %s (or leave blank to hide).', 'crispy-theme' ), esc_html( $client_name ) );
			?>
		</p>
		<?php
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

	/**
	 * Render the shortcodes section description.
	 *
	 * @return void
	 */
	public function render_shortcodes_section(): void {
		echo '<p>' . esc_html__(
			'Define shortcodes to expand when copying or viewing as Markdown. One per line: [shortcode] = replacement text. Replacement text can include Markdown formatting.',
			'crispy-theme'
		) . '</p>';
	}

	/**
	 * Render the shortcodes textarea field.
	 *
	 * @return void
	 */
	public function render_shortcodes_field(): void {
		$value = get_option( 'crispytheme_markdown_shortcodes', '' );
		?>
		<textarea
			name="crispytheme_markdown_shortcodes"
			id="crispytheme_markdown_shortcodes"
			rows="6"
			cols="60"
			class="large-text code"
		><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Example: [postsignature] = **Christopher S. Penn** - Marketing AI Expert', 'crispy-theme' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize the markdown shortcodes option.
	 *
	 * @param mixed $input The submitted value.
	 * @return string The sanitized value.
	 */
	public function sanitize_markdown_shortcodes( $input ): string {
		if ( ! is_string( $input ) ) {
			return '';
		}

		// Sanitize each line while preserving markdown formatting.
		$lines     = explode( "\n", $input );
		$sanitized = array_map(
			static function ( string $line ): string {
				// Use wp_kses_post to allow basic formatting while sanitizing.
				return wp_kses_post( trim( $line ) );
			},
			$lines
		);

		return implode( "\n", $sanitized );
	}
}
