<?php
/**
 * Proof Unit Tests.
 *
 * Recent rooms and also spoken at stay apart (Penn, 2026-10-26). Recent rooms hold Penn-led rooms of the
 * last 18 months with month and year. A stage date before 2025 shows outside the also-spoken block only
 * beside a repeat booking. The browser check src/stage_dates.mjs repeats the rule on the rendered pages.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\Proof;
use CrispyTheme\Booking\ProblemPicker;

beforeEach(function () {
    Functions\when('get_option')->alias(static fn(string $name, mixed $default = false): mixed => $default);
    Functions\when('get_transient')->justReturn(false);
    Functions\when('esc_url')->alias(static fn(string $url): string => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
});

describe('Proof recent rooms', function () {
    it('holds the eleven Penn-led rooms, newest first, each with a month and a year', function () {
        $rows = Proof::rooms();

        expect($rows)->toHaveCount(11);
        expect(array_column($rows, 'when'))->toBe(['2026-10', '2026-09', '2026-09', '2026-05', '2026-04', '2026-03', '2026-02', '2025-11', '2025-08', '2025-08', '2025-07']);
        expect(array_column($rows, 'room'))->toContain('Okta', 'MASFAA', 'AMA Central PA', 'JLL', 'Self Storage Institute', 'Spotlight Summit', 'LPA', 'Harvard Federal Credit Union');
    });

    it('stays inside 18 months of the newest room and before nothing older', function () {
        // The newest room is October 2026, so the window opens April 2025. The browser check uses the date of the day.
        foreach (Proof::rooms() as $row) {
            expect($row['when'] >= '2025-04')->toBeTrue($row['room']);
        }
    });

    it('never lists a room that Katie led or shared, or a name Penn has not confirmed', function () {
        $text = json_encode(Proof::rooms());

        foreach (['Katie', 'MEMA', 'Event Nexus', 'NTCA', 'Verato', 'Joist', 'Dreamforce', 'INBOUND', 'World of Watson'] as $banned) {
            expect($text)->not->toContain($banned);
        }
    });

    it('shows a repeat booking for the Tourism Industry Association of Alberta and for LPA only', function () {
        $again = array_filter(array_column(Proof::rooms(), 'again', 'room'));

        expect($again)->toBe([
            'LPA' => 'Booked again: April 2024 and September 2026',
            'Tourism Industry Association of Alberta, Alberta Tourism Advocacy Summit' => 'Booked again: 2025 and 2026',
        ]);
    });

    it('prints a real list with a heading of its own, the month and year as the second figure, and the repeat marks', function () {
        $html = Proof::rooms_section(true);

        expect($html)->toContain('<h2 id="bp-rooms-h" data-reveal>Recent rooms</h2>');
        expect($html)->toContain('<ol class="rooms__list"');
        expect($html)->toContain('<time datetime="2026-10">October 2026</time>');
        expect(substr_count($html, '<li class="room"'))->toBe(11);
        expect(substr_count($html, 'class="room__again"'))->toBe(2);
    });
});

describe('Proof also spoken at', function () {
    it('lists the eight older stages that Penn named, in a block that the date check skips', function () {
        $html = Proof::also_section(true);

        expect($html)->toContain('data-also');
        expect(substr_count($html, '<li class="also__stage">'))->toBe(8);
        foreach (['IBM Think', 'IBM World of Watson, 2016', 'Dreamforce, ', 'INBOUND, ', 'Social Media Marketing World', 'Content Marketing World', 'MarketingProfs B2B Forum', 'MAICON, 2019 opening keynote and 2026'] as $stage) {
            expect($html)->toContain($stage);
        }
    });
});

describe('Proof letter, reach, and promotion', function () {
    it('prints the April 2024 letter only beside the line that LPA booked again', function () {
        foreach ([true, false] as $first) {
            $html = Proof::letter_section(true, $first);

            expect($html)->toContain('President, LPA, April 2024');
            expect($html)->toMatch('/LPA booked (me|Christopher Penn) again in September 2026\./');
        }
    });

    it('states the reach with the month of each count and no unverified figure', function () {
        // Another test file may load inc/template-tags.php. Then the real functions run on the option defaults.
        $real = function_exists('crispytheme_get_subscriber_count')
            && str_ends_with((string) (new ReflectionFunction('crispytheme_get_subscriber_count'))->getFileName(), 'template-tags.php');
        if (!$real) {
            Functions\when('crispytheme_get_subscriber_count')->justReturn('About 292K');
            Functions\when('crispytheme_get_subscriber_as_of')->justReturn('October 2026');
        }

        expect(Proof::reach())->toBe('About 292K newsletter subscribers (October 2026). 49,269 LinkedIn followers (October 2026).');
    });

    it('states the promotion for a full-fee keynote with no count and no cadence', function () {
        expect(Proof::PROMOTION)->toBe('A full-fee keynote includes promotion of your event: newsletter inclusion, social media posts, and a promotional video clip.');
        expect(Proof::PROMOTION)->not->toMatch('/\d/');
        expect(Proof::PROMOTION)->not->toMatch('/weekly|daily|monthly|per |each /i');
        expect(ProblemPicker::QUESTION)->toContain('problem');
    });
});
