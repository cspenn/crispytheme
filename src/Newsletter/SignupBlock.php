<?php
/**
 * Newsletter Signup Block.
 *
 * Server-rendered block for Substack newsletter embed or custom signup form.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Newsletter;

/**
 * Newsletter Signup Block class.
 */
class SignupBlock {

	/**
	 * Default Substack URL.
	 */
	private const DEFAULT_SUBSTACK_URL = 'https://almosttimely.substack.com';

	/**
	 * Initialize the block.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the newsletter signup block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			'crispytheme/newsletter-signup',
			[
				'api_version'     => '3',
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'substackUrl'         => [
						'type'    => 'string',
						'default' => self::DEFAULT_SUBSTACK_URL,
					],
					'heading'             => [
						'type'    => 'string',
						'default' => '',
					],
					'description'         => [
						'type'    => 'string',
						'default' => '',
					],
					'showSubscriberCount' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'subscriberCount'     => [
						'type'    => 'string',
						'default' => '294,000+',
					],
					'buttonText'          => [
						'type'    => 'string',
						'default' => 'Subscribe',
					],
					'style'               => [
						'type'    => 'string',
						'default' => 'hero',
					],
				],
			]
		);
	}

	/**
	 * Render the newsletter signup block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Rendered block HTML.
	 */
	public function render_block( array $attributes ): string {
		$substack_url     = esc_url( $attributes['substackUrl'] ?? self::DEFAULT_SUBSTACK_URL );
		$heading          = wp_kses_post( $attributes['heading'] ?? '' );
		$description      = wp_kses_post( $attributes['description'] ?? '' );
		$show_count       = (bool) ( $attributes['showSubscriberCount'] ?? false );
		$subscriber_count = esc_html( $attributes['subscriberCount'] ?? '294,000+' );
		$button_text      = esc_html( $attributes['buttonText'] ?? __( 'Subscribe', 'crispy-theme' ) );
		$style            = esc_attr( $attributes['style'] ?? 'hero' );

		// Build the embed URL.
		$embed_url = trailingslashit( $substack_url ) . 'embed';

		$class_name = 'newsletter-signup';
		if ( 'inline' === $style ) {
			$class_name = 'newsletter-inline';
		}

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class' => $class_name,
			]
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $show_count ) : ?>
				<span class="newsletter-subscriber-count"><?php echo $subscriber_count; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="newsletter-subscriber-label"><?php esc_html_e( 'subscribers', 'crispy-theme' ); ?></span>
			<?php endif; ?>

			<?php if ( $heading ) : ?>
				<h3 class="newsletter-heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3>
			<?php endif; ?>

			<?php if ( $description ) : ?>
				<p class="newsletter-description"><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>

			<div class="newsletter-form-wrapper">
				<iframe
					src="<?php echo esc_url( $embed_url ); ?>"
					width="100%"
					height="150"
					style="border:none; background:transparent;"
					frameborder="0"
					scrolling="no"
					title="<?php esc_attr_e( 'Newsletter signup form', 'crispy-theme' ); ?>"
				></iframe>
			</div>

			<p class="newsletter-privacy">
				<?php
				$privacy_url = get_privacy_policy_url();
				if ( $privacy_url ) {
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Privacy policy URL */
							__( 'Your privacy is protected. <a href="%s">Privacy Policy</a>', 'crispy-theme' ),
							esc_url( $privacy_url )
						)
					);
				} else {
					esc_html_e( 'Your privacy is protected.', 'crispy-theme' );
				}
				?>
			</p>
		</div>
		<?php
		return ob_get_clean() ?: '';
	}
}
