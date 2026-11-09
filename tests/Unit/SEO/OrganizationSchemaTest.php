<?php
/**
 * OrganizationSchema Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\OrganizationSchema;
use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('OrganizationSchema', function () {
    beforeEach(function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('home_url')->alias(
            static fn(string $path = ''): string => 'https://example.com' . $path
        );
    });

    it('returns null when org name is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_org_name' === $key ? '' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );

        expect($builder->build())->toBeNull();
    });

    it('includes required @context and @type', function () {
        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['@context'])->toBe('https://schema.org');
        expect($result['@type'])->toBe('Organization');
    });

    it('includes org name', function () {
        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['name'])->toBe('Christopher S. Penn');
    });

    it('includes contactPoint with booking URL', function () {
        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['contactPoint']['@type'])->toBe('ContactPoint');
        expect($result['contactPoint']['contactType'])->toBe('Booking Inquiry');
        expect($result['contactPoint']['url'])->toBe('https://example.com/contact-me/');
    });

    it('resolves relative booking path to absolute URL in contactPoint', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_booking_url' === $key ? '/speak/' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['contactPoint']['url'])->toBe('https://example.com/speak/');
    });

    it('omits email from contactPoint when booking email is empty', function () {
        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['contactPoint'])->not->toHaveKey('email');
    });

    it('includes email in contactPoint when booking email is set', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_booking_email' === $key ? 'book@example.com' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['contactPoint']['email'])->toBe('book@example.com');
    });

    it('omits logo when org logo option is empty', function () {
        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('logo');
    });

    it('includes logo when org logo option is set', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_org_logo' === $key ? 'https://example.com/logo.png' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new OrganizationSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['logo'])->toBe('https://example.com/logo.png');
    });
});
