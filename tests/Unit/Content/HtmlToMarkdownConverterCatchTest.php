<?php
/**
 * HtmlToMarkdownConverter catch-path Unit Test.
 *
 * Covers the exception-handling branch in convert() by injecting a converter
 * that throws (now possible via constructor dependency injection).
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\HtmlToMarkdownConverter;
use League\HTMLToMarkdown\HtmlConverter;

it('returns an empty string when the underlying converter throws', function () {
    $throwing = new class() extends HtmlConverter {
        public function convert($html): string {
            throw new \RuntimeException( 'conversion failed' );
        }
    };

    $converter = new HtmlToMarkdownConverter( $throwing );

    expect( $converter->convert( '<p>Some HTML content</p>' ) )->toBe( '' );
});
