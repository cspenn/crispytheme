<?php
/**
 * BookingFacts Unit Tests.
 *
 * The facts of the booking page live in Theme Options. These tests check the defaults (the values
 * Christopher Penn stated on 2026-10-25), the fallback of a blank option, and the formatters.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\BookingFacts;

/**
 * Stub get_option with a map of option name => value. A missing name returns the default.
 *
 * @param array<string, string> $options Option values.
 */
function crispyBookingOptions(array $options = []): void
{
    Functions\when('get_option')->alias(
        static fn(string $name, mixed $default = false): mixed => $options[$name] ?? $default
    );
}

describe('BookingFacts defaults', function () {
    it('holds the facts Penn stated on 2026-10-25 and 2026-10-26', function () {
        crispyBookingOptions();

        expect(BookingFacts::money('fee_keynote_from'))->toBe('$25,000');
        expect(array_keys(BookingFacts::definitions()))->not->toContain('fee_assoc_low', 'fee_assoc_high', 'fee_exec_from');
        expect(BookingFacts::money('fee_workshop_from'))->toBe('$25,000');
        expect(BookingFacts::percent('deposit_percent'))->toBe('50%');
        expect(BookingFacts::int('refund_over_6_months'))->toBe(75);
        expect(BookingFacts::int('refund_3_to_6_months'))->toBe(50);
        expect(BookingFacts::int('refund_under_3_months'))->toBe(0);
        expect(BookingFacts::int('balance_days'))->toBe(30);
        expect(BookingFacts::int('reimburse_days'))->toBe(30);
        expect(BookingFacts::int('fit_call_minutes'))->toBe(30);
        expect(BookingFacts::int('prep_call_minutes'))->toBe(30);
        expect(BookingFacts::get('ibm_champion_times'))->toBe('eight');
        expect(BookingFacts::int('ibm_final_year'))->toBe(2025);
        expect(BookingFacts::int('book_titles'))->toBe(14);
        expect(BookingFacts::int('book_editions'))->toBe(19);
        expect(BookingFacts::get('quote_date'))->toBe('April 2024');
        expect(BookingFacts::int('linkedin_followers'))->toBe(49269);
        expect(BookingFacts::get('linkedin_as_of'))->toBe('October 2026');
        expect(BookingFacts::get('facts_as_of'))->toBe('2026-10-26');
    });

    it('names exactly the 15 companies Penn chose, and never "500+ events"', function () {
        crispyBookingOptions();

        $names = array_map('trim', explode(',', BookingFacts::get('client_names')));

        expect($names)->toHaveCount(15);
        expect($names)->toContain('Pfizer', "McDonald's", 'T-Mobile', 'Gentex');
        expect(implode(' ', array_column(BookingFacts::definitions(), 'default')))->not->toContain('500+');
    });

    it('gives every fact a label, a kind, and a prefixed option name', function () {
        foreach (BookingFacts::definitions() as $key => $definition) {
            expect($definition['label'])->not->toBe('');
            expect($definition['kind'])->toBeIn(['text', 'number', 'textarea', 'url']);
            expect(BookingFacts::option_name($key))->toStartWith('crispytheme_booking_');
        }
    });

    it('keeps the reel hidden by default', function () {
        crispyBookingOptions();

        expect(BookingFacts::get('reel_url'))->toBe('');
    });
});

describe('BookingFacts options', function () {
    it('reads an option over the default', function () {
        crispyBookingOptions(['crispytheme_booking_fee_keynote_from' => '60000']);

        expect(BookingFacts::money('fee_keynote_from'))->toBe('$60,000');
    });

    it('falls back to the default when an option is blank', function () {
        crispyBookingOptions(['crispytheme_booking_balance_days' => '   ']);

        expect(BookingFacts::int('balance_days'))->toBe(30);
    });

    it('keeps a blank reel option blank', function () {
        crispyBookingOptions(['crispytheme_booking_reel_url' => '']);

        expect(BookingFacts::get('reel_url'))->toBe('');
    });

    it('strips non-digits from a number', function () {
        crispyBookingOptions(['crispytheme_booking_fee_keynote_from' => '$27,500']);

        expect(BookingFacts::int('fee_keynote_from'))->toBe(27500);
        expect(BookingFacts::money('fee_keynote_from'))->toBe('$27,500');
    });
});
