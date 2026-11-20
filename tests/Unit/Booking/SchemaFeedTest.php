<?php
/**
 * SchemaFeed Unit Tests.
 *
 * The feed gives the CrispySEO plugin the facts for the JSON-LD graph. These tests check that the
 * feed reads the same source as the visible page and states only what the facts files state.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\BookingFacts;
use CrispyTheme\Booking\BookingPage;
use CrispyTheme\Booking\SchemaFeed;

/**
 * Mock the WordPress functions that the feed and the page use.
 *
 * @param array<string, string> $options Option values by full option name.
 * @param bool                  $is_booking Whether the request is the booking page.
 * @param bool                  $is_front Whether the request is the front page.
 */
function crispyFeedSetup(array $options = [], bool $is_booking = true, bool $is_front = false): void
{
    Functions\when('get_option')->alias(
        static fn(string $name, mixed $default = false): mixed => $options[$name] ?? $default
    );
    Functions\when('home_url')->alias(static fn(string $path = ''): string => 'https://example.com' . $path);
    Functions\when('is_page')->justReturn($is_booking);
    Functions\when('is_front_page')->justReturn($is_front);
    Functions\when('esc_url')->alias(static fn(string $url): string => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    Functions\when('wp_date')->justReturn('2026');
    crispySubscriberSetup();
    Functions\when('attachment_url_to_postid')->justReturn(0);
    Functions\when('get_post_time')->justReturn('');
}

describe('SchemaFeed', function () {

    it('registers the profile filter that the plugin applies', function () {
        $feed = new SchemaFeed();
        $feed->init();

        expect(has_filter('crispy_seo_speaker_profile', [$feed, 'profile']))->not->toBeFalse();
    });

    it('prints a FAQ that equals the visible page text, after the HTML is decoded', function () {
        crispyFeedSetup();

        $html = BookingPage::html();
        preg_match('#<div class="bp-faq__text">(.*?)</div></div></section>#s', $html, $match);
        // The first three answers are open text. The last four fold behind a native details element.
        preg_match_all('#<p><strong>(.*?)</strong> (.*?)</p>|<details[^>]*><summary>(.*?)</summary><p>(.*?)</p></details>#s', $match[1], $found, PREG_SET_ORDER);
        $rows = array_map(
            static fn(array $row): array => isset($row[3]) && '' !== $row[3] ? [1 => $row[3], 2 => $row[4]] : [1 => $row[1], 2 => $row[2]],
            $found
        );

        $visible = array_map(
            static fn(array $row): array => [
                'question' => html_entity_decode($row[1], ENT_QUOTES, 'UTF-8'),
                'answer'   => html_entity_decode($row[2], ENT_QUOTES, 'UTF-8'),
            ],
            $rows
        );

        expect($visible)->toHaveCount(7);
        expect(SchemaFeed::faq())->toBe($visible);
    });

    it('adds the FAQ only on the booking page', function () {
        crispyFeedSetup([], false, false);

        $profile = (new SchemaFeed())->profile([]);

        expect($profile['faq'])->toBe([]);

        crispyFeedSetup([], true, false);

        expect((new SchemaFeed())->profile([])['faq'])->toHaveCount(7);
    });

    it('gives an absolute booking URL that points at the check-my-dates form', function () {
        crispyFeedSetup();

        expect((new SchemaFeed())->profile([])['booking_url'])->toBe('https://example.com/public-speaking/#check-my-dates');
    });

    it('states the IBM Champion credential as the facts file states it', function () {
        crispyFeedSetup();

        $person = (new SchemaFeed())->profile([])['person'];

        expect($person['credentials'])->toHaveCount(1);
        expect($person['credentials'][0]['name'])->toBe('IBM Champion');
        expect($person['credentials'][0]['description'])->toBe('Eight-time IBM Champion (2025 was the final year)');
    });

    it('follows the IBM facts when Penn changes them', function () {
        crispyFeedSetup([
            BookingFacts::option_name('ibm_champion_times') => 'nine',
            BookingFacts::option_name('ibm_final_year')     => '2026',
        ]);

        $description = (new SchemaFeed())->profile([])['person']['credentials'][0]['description'];

        expect($description)->toBe('Nine-time IBM Champion (2026 was the final year)');
    });

    it('names the job title, the talks, and the profile links of the files', function () {
        crispyFeedSetup();

        $person = (new SchemaFeed())->profile([])['person'];

        expect($person['name'])->toBe('Christopher S. Penn');
        expect($person['job_title'])->toBe('AI builder and keynote speaker');
        expect($person['knows_about'])->toBe(['The Five Levels of AI Enablement', 'Nobody Decided This']);
        expect($person['same_as'])->toBe(['https://www.linkedin.com/in/christopherspenn/', 'https://almosttimely.substack.com']);
    });

    it('reads the sameAs option as one URL per line', function () {
        crispyFeedSetup(['crispytheme_schema_same_as' => "https://one.example/a\r\n\r\nhttps://two.example/b "]);

        expect((new SchemaFeed())->profile([])['person']['same_as'])->toBe(['https://one.example/a', 'https://two.example/b']);
    });

    it('adds the video only when the reel option is set, and only on the booking and front pages', function () {
        crispyFeedSetup();
        expect((new SchemaFeed())->profile([])['video'])->toBe([]);

        crispyFeedSetup([BookingFacts::option_name('reel_url') => 'https://example.com/reel.mp4']);
        $video = (new SchemaFeed())->profile([])['video'];
        expect($video['content_url'])->toBe('https://example.com/reel.mp4');
        expect($video['upload_date'])->toBe('');

        crispyFeedSetup([BookingFacts::option_name('reel_url') => 'https://example.com/reel.mp4'], false, false);
        expect((new SchemaFeed())->profile([])['video'])->toBe([]);

        crispyFeedSetup([BookingFacts::option_name('reel_url') => 'https://example.com/reel.mp4'], false, true);
        expect((new SchemaFeed())->profile([])['video']['content_url'])->toBe('https://example.com/reel.mp4');
    });

    it('gives a YouTube reel an embed URL and no content URL', function () {
        crispyFeedSetup([BookingFacts::option_name('reel_url') => 'https://www.youtube.com/watch?v=w7uxV3PjGXA']);
        $video = (new SchemaFeed())->profile([])['video'];

        expect($video['content_url'])->toBe('');
        expect($video['embed_url'])->toBe('https://www.youtube-nocookie.com/embed/w7uxV3PjGXA');
    });

    it('reads the reel upload date from the option first, then from the Media Library item', function () {
        $reel = BookingFacts::option_name('reel_url');

        crispyFeedSetup([$reel => 'https://example.com/reel.mp4', 'crispytheme_schema_video_upload_date' => '2026-11-01']);
        Functions\when('attachment_url_to_postid')->justReturn(77);
        Functions\when('get_post_time')->justReturn('2026-12-24T10:00:00+00:00');
        expect((new SchemaFeed())->profile([])['video']['upload_date'])->toBe('2026-11-01');

        crispyFeedSetup([$reel => 'https://example.com/reel.mp4']);
        Functions\when('attachment_url_to_postid')->justReturn(77);
        Functions\when('get_post_time')->justReturn('2026-12-24T10:00:00+00:00');
        expect((new SchemaFeed())->profile([])['video']['upload_date'])->toBe('2026-12-24T10:00:00+00:00');

        crispyFeedSetup([$reel => 'https://example.com/reel.mp4']);
        Functions\when('attachment_url_to_postid')->justReturn(0);
        expect((new SchemaFeed())->profile([])['video']['upload_date'])->toBe('');
    });

    it('gives the entity a name that is not the site title, and follows its own option', function () {
        crispyFeedSetup();
        expect((new SchemaFeed())->profile([])['organization_name'])->toBe('Christopher S. Penn');

        crispyFeedSetup(['crispytheme_schema_org_name' => 'Penn Speaking Studio', 'blogname' => 'A Different Site Title']);
        expect((new SchemaFeed())->profile([])['organization_name'])->toBe('Penn Speaking Studio');
    });

    it('marks the speakable summary on the front page only', function () {
        crispyFeedSetup([], false, true);
        expect((new SchemaFeed())->profile([])['speakable'])->toBe(['.speakable-summary']);

        crispyFeedSetup([], true, false);
        expect((new SchemaFeed())->profile([])['speakable'])->toBe([]);
    });

    it('lists the five levels from the canonical slide and keeps the key pages for llms.txt', function () {
        crispyFeedSetup();

        $profile = (new SchemaFeed())->profile([]);

        expect($profile['levels']['title'])->toBe('The Five Levels of AI Enablement');
        expect($profile['levels']['items'])->toHaveCount(5);
        expect($profile['levels']['items'][0])->toBe('Level 1, Done by you: Workflow. You’re doing all the work.');
        expect($profile['key_pages'])->toBe(['/public-speaking/', '/about/', '/newsletter/', '/contact-me/']);
    });

    it('carries no Event, Review, or rating claim', function () {
        crispyFeedSetup([BookingFacts::option_name('reel_url') => 'https://example.com/reel.mp4']);

        $json = (string) json_encode((new SchemaFeed())->profile([]));

        expect($json)->not->toMatch('/"(Event|Review|aggregateRating|AggregateRating|Rating)"/');
        expect($json)->not->toContain('500+');
        expect($json)->not->toContain('98%');
    });

    it('keeps a non-array profile from breaking the filter chain', function () {
        crispyFeedSetup();

        expect((new SchemaFeed())->profile(null))->toHaveKey('person');
    });
});
