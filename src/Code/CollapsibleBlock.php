<?php
/**
 * Collapsible Code Block.
 *
 * Server-rendered block for collapsible code sections with syntax highlighting.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\Code;

/**
 * Collapsible Code Block class.
 */
class CollapsibleBlock {

	/**
	 * Initialize the block.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the collapsible code block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		register_block_type(
			'crispytheme/collapsible-code',
			[
				'api_version'     => '3',
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'title'           => [
						'type'    => 'string',
						'default' => 'Show Code',
					],
					'code'            => [
						'type'    => 'string',
						'default' => '',
					],
					'language'        => [
						'type'    => 'string',
						'default' => 'python',
					],
					'startOpen'       => [
						'type'    => 'boolean',
						'default' => false,
					],
					'showLineNumbers' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'filename'        => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);
	}

	/**
	 * Render the collapsible code block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Rendered block HTML.
	 */
	public function render_block( array $attributes ): string {
		$title      = esc_html( $attributes['title'] ?? __( 'Show Code', 'crispy-theme' ) );
		$code       = $attributes['code'] ?? '';
		$language   = esc_attr( $attributes['language'] ?? 'python' );
		$start_open = (bool) ( $attributes['startOpen'] ?? false );
		$line_nums  = (bool) ( $attributes['showLineNumbers'] ?? true );
		$filename   = esc_html( $attributes['filename'] ?? '' );

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class' => 'collapsible-code',
			]
		);

		$open_attr  = $start_open ? ' open' : '';
		$line_class = $line_nums ? ' line-numbers' : '';

		ob_start();
		?>
		<details <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $open_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<summary>
				<?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $filename ) : ?>
					<span class="collapsible-code-filename"><?php echo $filename; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
			</summary>
			<pre class="language-<?php echo $language; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $line_class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><code class="language-<?php echo $language; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"><?php echo esc_html( $code ); ?></code></pre>
		</details>
		<?php
		return ob_get_clean() ?: '';
	}
}
