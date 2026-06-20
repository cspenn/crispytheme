<?php
/**
 * VideoObjectSchema Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\SEO
 */

declare(strict_types=1);

use CrispyTheme\SEO\VideoObjectSchema;
use CrispyTheme\SEO\SchemaSettings;
use Brain\Monkey\Functions;

describe('VideoObjectSchema', function () {
    beforeEach(function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
    });

    it('returns null when video URL is empty', function () {
        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );

        expect($builder->build())->toBeNull();
    });

    it('includes required @context and @type when video URL is set', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['@context'])->toBe('https://schema.org');
        expect($result['@type'])->toBe('VideoObject');
    });

    it('sets contentUrl and embedUrl to the video URL', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['contentUrl'])->toBe('https://vimeo.com/123456');
        expect($result['embedUrl'])->toBe('https://vimeo.com/123456');
    });

    it('includes default video name', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['name'])->toBe('Christopher Penn AI Keynote Demo Reel');
    });

    it('omits description when video description option is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('description');
    });

    it('includes description when configured', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => match ($key) {
                'crispytheme_schema_video_url'         => 'https://vimeo.com/123456',
                'crispytheme_schema_video_description' => 'A great keynote reel.',
                default                                => $default,
            }
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['description'])->toBe('A great keynote reel.');
    });

    it('omits thumbnailUrl when option is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('thumbnailUrl');
    });

    it('includes thumbnailUrl when configured', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => match ($key) {
                'crispytheme_schema_video_url'       => 'https://vimeo.com/123456',
                'crispytheme_schema_video_thumbnail' => 'https://example.com/thumb.jpg',
                default                              => $default,
            }
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['thumbnailUrl'])->toBe('https://example.com/thumb.jpg');
    });

    it('omits duration when option is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('duration');
    });

    it('includes duration in ISO 8601 format when configured', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => match ($key) {
                'crispytheme_schema_video_url'      => 'https://vimeo.com/123456',
                'crispytheme_schema_video_duration' => 'PT2M30S',
                default                             => $default,
            }
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['duration'])->toBe('PT2M30S');
    });

    it('omits uploadDate when option is empty', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed =>
                'crispytheme_schema_video_url' === $key ? 'https://vimeo.com/123456' : $default
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result)->not->toHaveKey('uploadDate');
    });

    it('includes uploadDate when configured', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => match ($key) {
                'crispytheme_schema_video_url'         => 'https://vimeo.com/123456',
                'crispytheme_schema_video_upload_date' => '2024-01-15',
                default                                => $default,
            }
        );

        $settings = new SchemaSettings();
        $builder  = new VideoObjectSchema( $settings );
        $result   = $builder->build();

        expect($result)->not->toBeNull();
        expect($result['uploadDate'])->toBe('2024-01-15');
    });
});
