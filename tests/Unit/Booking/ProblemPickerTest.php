<?php
/**
 * ProblemPicker Unit Tests.
 *
 * The six problems are the front door (Penn, 2026-10-26). A room shows only when a file backs it. The form
 * field, the page, and the script all read one list, so each test reads that list.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\ProblemPicker;

beforeEach(function () {
    Functions\when('get_option')->alias(static fn(string $name, mixed $default = false): mixed => $default);
    Functions\when('esc_url')->alias(static fn(string $url): string => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
});

describe('ProblemPicker list', function () {
    it('holds the six problems in the order Penn gave, with the last choice for a problem that is not listed', function () {
        expect(ProblemPicker::choices())->toBe([
            'AI enablement and adoption: our people do not use it well',
            'AI visibility: people no longer find us',
            'AI skills and judgment: our people stop thinking',
            'AI in daily work: where does it save time',
            'AI governance and data privacy: we fear leaks and lock-in',
            'AI analytics: we cannot trust the numbers',
            'Something else, or not sure yet',
        ]);
        expect(ProblemPicker::QUESTION)->toBe('What is your audience’s main problem?');
        expect(ProblemPicker::FIELD_LABEL)->toBe("What is your audience's main problem?");
    });

    it('names the approved talk of each problem, and no title where none is approved', function () {
        $talks = array_column(ProblemPicker::problems(), 'talk', 'slug');

        expect($talks)->toBe([
            'enablement' => 'The Five Levels of AI Enablement',
            'visibility' => '',
            'judgment'   => 'Nobody Decided This',
            'daily-work' => 'Never Think Alone',
            'governance' => 'Who Controls Your AI',
            'analytics'  => 'Build an Analytics Powerhouse with AI',
        ]);
    });

    it('shows a recent room only where a file backs it, and never pads a missing room', function () {
        $rooms = [];
        foreach (ProblemPicker::problems() as $problem) {
            $rooms[$problem['slug']] = ProblemPicker::room($problem);
        }

        expect($rooms)->toBe([
            'enablement' => 'Self Storage Institute, April 2026',
            'visibility' => 'Spotlight Summit, September 2026',
            'judgment'   => 'Alberta Tourism Advocacy Summit, February 2026',
            'daily-work' => 'JLL and AMA Central PA, August 2025',
            'governance' => '',
            'analytics'  => 'MAICON, October 2026',
        ]);
    });

    it('states the promise of visibility and analytics in the words of the recording and the session page', function () {
        $promises = array_column(ProblemPicker::problems(), 'promise', 'slug');

        expect($promises['visibility'])->toBe('AI reads text, not design.');
        expect($promises['analytics'])->toBe('Do data analysis with the AI tools you already have, with no coding.');
    });

    it('turns a year and month into words', function () {
        expect(ProblemPicker::month_year('2026-04'))->toBe('April 2026');
        expect(ProblemPicker::month_year('2025-12'))->toBe('December 2025');
    });
});

describe('ProblemPicker markup', function () {
    it('prints a radio group of six rows in a plain form, each radio carrying the problem and the topic', function () {
        $menu = ProblemPicker::menu('');

        expect($menu)->toContain('<form class="pk" method="get" action="/public-speaking/#check-my-dates"');
        expect(substr_count($menu, 'class="pk__item"'))->toBe(6);
        expect(substr_count($menu, 'type="radio" name="problem"'))->toBe(6);
        expect($menu)->toContain('value="AI analytics: we cannot trust the numbers" data-topic="Build an Analytics Powerhouse with AI"');
        expect($menu)->toContain('value="AI visibility: people no longer find us" data-topic="AI visibility"');
        // Every radio has its own label, so a tap on the name picks the row, and the first row is chosen.
        expect(substr_count($menu, '<label class="pk__label" for="pk-'))->toBe(6);
        expect(substr_count($menu, ' checked '))->toBe(1);
    });

    it('builds the address that fills the problem and the topic of the form', function () {
        expect(ProblemPicker::link('AI analytics: we cannot trust the numbers', 'Build an Analytics Powerhouse with AI'))
            ->toBe('/public-speaking/?problem=AI%20analytics%3A%20we%20cannot%20trust%20the%20numbers&topic=Build%20an%20Analytics%20Powerhouse%20with%20AI#check-my-dates');
    });

    it('prints no talk element for visibility and no room element for governance', function () {
        $rows = explode('</li>', ProblemPicker::menu(''));

        expect($rows[1])->not->toContain('pk__talk');
        expect($rows[1])->toContain('pk__room');
        expect($rows[4])->toContain('pk__talk');
        expect($rows[4])->not->toContain('pk__room');
    });

    it('gives the planner a plain link when no problem fits', function () {
        expect(ProblemPicker::other_link())->toContain('?problem=Something%20else%2C%20or%20not%20sure%20yet#check-my-dates');
    });
});
