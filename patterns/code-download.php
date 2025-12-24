<?php
/**
 * Code Download Pattern.
 *
 * Code block with download button for sharing code snippets.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

if ( ! function_exists( 'register_block_pattern' ) ) {
	return;
}

register_block_pattern(
	'crispytheme/code-download',
	[
		'title'       => __( 'Code with Download', 'crispy-theme' ),
		'description' => __( 'Code block with a download button.', 'crispy-theme' ),
		'categories'  => [ 'crispytheme-code', 'crispytheme' ],
		'keywords'    => [ 'code', 'download', 'snippet', 'python', 'programming' ],
		'content'     => '<!-- wp:group {"className":"code-download-block","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
<div class="wp-block-group code-download-block" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"code-download-header","layout":{"type":"flex","justifyContent":"space-between"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#1e293b"}}} -->
	<div class="wp-block-group code-download-header has-background" style="background-color:#1e293b;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30)">
		<!-- wp:paragraph {"style":{"color":{"text":"#94a3b8"},"typography":{"fontSize":"0.875rem","fontWeight":"600"}},"fontFamily":"mono"} -->
		<p class="has-text-color has-mono-font-family" style="color:#94a3b8;font-size:0.875rem;font-weight:600">example.py</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","fontSize":"small","className":"code-download-btn"} -->
			<div class="wp-block-button has-custom-font-size is-style-fill has-small-font-size code-download-btn"><a class="wp-block-button__link has-primary-background-color has-background wp-element-button" href="#">' . esc_html__( 'Download', 'crispy-theme' ) . '</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:code {"className":"language-python"} -->
	<pre class="wp-block-code language-python"><code class="language-python"># Example Python code for AI prompt engineering
import openai

def generate_response(prompt: str) -> str:
    """Generate a response using GPT-4."""
    response = openai.ChatCompletion.create(
        model="gpt-4",
        messages=[
            {"role": "system", "content": "You are a helpful assistant."},
            {"role": "user", "content": prompt}
        ],
        temperature=0.7
    )
    return response.choices[0].message.content

# Usage example
result = generate_response("Explain machine learning in simple terms")
print(result)</code></pre>
	<!-- /wp:code -->
</div>
<!-- /wp:group -->',
	]
);
