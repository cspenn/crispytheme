<?php
/**
 * SpeakableSchema Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\SpeakableSchema;
use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('SpeakableSchema', function () {
    beforeEach(function () {
        // Set up global $wp for SchemaSettings::get_current_url().
        global $wp;
        $wp = new stdClass();
        $wp->request = '';

        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );
        Functions\when('add_query_arg')->alias(
            static fn(array $args, string $url = ''): string => $url
        );
    });

    it('returns a non-null result', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );

        expect($builder->build())->not->toBeNull();
    });

    it('includes @context https://schema.org', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );
        $result   = $builder->build();

        expect($result['@context'])->toBe('https://schema.org');
    });

    it('includes @type WebPage', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );
        $result   = $builder->build();

        expect($result['@type'])->toBe('WebPage');
    });

    it('includes url field', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );
        $result   = $builder->build();

        expect($result)->toHaveKey('url');
    });

    it('includes speakable SpeakableSpecification', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );
        $result   = $builder->build();

        expect($result['speakable']['@type'])->toBe('SpeakableSpecification');
    });

    it('targets .speakable-summary CSS selector', function () {
        $settings = new SchemaSettings();
        $builder  = new SpeakableSchema( $settings );
        $result   = $builder->build();

        expect($result['speakable']['cssSelector'])->toContain('.speakable-summary');
    });
});
