<?php
/**
 * PersonSchema Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\PersonSchema;
use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('PersonSchema', function () {
    beforeEach(function () {
        // Default mocks — options return their defaults; home_url resolves simply.
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );
    });

    it('returns null when person name is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_person_name' === $key ? '' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );

        expect($builder->build())->toBeNull();
    });

    it('includes required @context and @type', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['@context'])->toBe('https://schema.org');
        expect($result['@type'])->toBe('Person');
    });

    it('includes person name', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['name'])->toBe('Christopher S. Penn');
    });

    it('includes jobTitle and description', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->toHaveKey('jobTitle');
        expect($result)->toHaveKey('description');
    });

    it('includes baked-in knowsAbout array', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['knowsAbout'])->toContain('Artificial Intelligence');
        expect($result['knowsAbout'])->toContain('Generative AI');
        expect($result['knowsAbout'])->toContain('Marketing Analytics');
        expect($result['knowsAbout'])->toContain('Data Science');
    });

    it('includes baked-in IBM Champion credential', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['hasCredential'])->toBeArray();
        expect($result['hasCredential'][0]['name'])->toBe('IBM Champion');
        expect($result['hasCredential'][0]['@type'])->toBe('EducationalOccupationalCredential');
    });

    it('includes baked-in Trust Insights affiliation', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['affiliation']['name'])->toBe('Trust Insights');
        expect($result['affiliation']['url'])->toBe('https://www.trustinsights.ai');
    });

    it('omits image field when option is empty', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('image');
    });

    it('includes image field when option is set', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_person_image' === $key ? 'https://example.com/headshot.jpg' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['image'])->toBe('https://example.com/headshot.jpg');
    });

    it('omits sameAs field when option is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key ? '' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('sameAs');
    });

    it('includes sameAs field when URLs are configured', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_same_as' === $key
                    ? "https://twitter.com/cspenn\nhttps://linkedin.com/in/cspenn"
                    : $default
        );

        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['sameAs'])->toContain('https://twitter.com/cspenn');
        expect($result['sameAs'])->toContain('https://linkedin.com/in/cspenn');
    });

    it('includes home URL', function () {
        $settings = new SchemaSettings();
        $builder  = new PersonSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['url'])->toBe('https://example.com/');
    });
});
