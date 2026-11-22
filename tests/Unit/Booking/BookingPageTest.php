<?php
/**
 * BookingPage Unit Tests.
 *
 * The output of the booking page must follow the Theme Options and the copy deck of 2026-10-25.
 * Each test sets an option, renders the page, and reads the HTML.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\BookingFacts;
use CrispyTheme\Booking\BookingPage;

/**
 * Render the page with chosen option values.
 *
 * @param array<string, string> $options Option values by fact key.
 */
function crispyBookingRender(array $options = []): string
{
    $named = [];
    foreach ($options as $key => $value) {
        $named[BookingFacts::option_name($key)] = $value;
    }

    Functions\when('get_option')->alias(
        static fn(string $name, mixed $default = false): mixed => $named[$name] ?? $default
    );
    Functions\when('esc_url')->alias(static fn(string $url): string => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    Functions\when('wp_date')->justReturn('2026');
    crispySubscriberSetup();

    return BookingPage::html();
}

/** Return the visible text of an HTML string. */
function crispyBookingText(string $html): string
{
    $spaced = (string) preg_replace('/<[^>]+>/', ' ', $html);

    return trim((string) preg_replace('/\s+/', ' ', html_entity_decode($spaced, ENT_QUOTES, 'UTF-8')));
}

describe('BookingPage structure', function () {
    it('has one h1 with the two-line headline of the copy deck', function () {
        $html = crispyBookingRender();

        expect(substr_count($html, '<h1'))->toBe(1);
        expect($html)->toContain('Book Christopher Penn<br>to keynote your event');
    });

    it('gives every section a heading', function () {
        $html = crispyBookingRender();

        foreach (['What is your audience’s main problem?', 'Promotion comes with the keynote', 'Recent rooms', 'What LPA wrote', 'Also spoken at', 'The Five Levels of AI Enablement', 'Fees and terms', 'Terms', 'How booking works', 'Planner questions', 'Check my dates'] as $heading) {
            expect($html)->toContain($heading);
        }
        expect(substr_count($html, '<h2'))->toBe(11);
    });

    it('sets the five level lines of the canonical slide in a real ordered list', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('<ol class="levels__list"');
        expect(substr_count($html, 'class="lv"'))->toBe(5);
        foreach ([
            'Workflow. You’re doing all the work.',
            'Automation. You’re doing some of the work.',
            'Agent. You’re doing very little of the work.',
            'Autonomous. You’re doing almost none of the work.',
            'Anticipatory. AI identifies needs before you do.',
        ] as $line) {
            expect($html)->toContain($line);
        }
        foreach (['Done by you', 'Done with you', 'Done for you', 'Done without you', 'Done ahead of you'] as $name) {
            expect($html)->toContain($name);
        }
    });

    it('gives each level a plain link that fills the topic field from the address', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('href="/public-speaking/?problem=AI%20enablement%20and%20adoption%3A%20our%20people%20do%20not%20use%20it%20well&amp;topic=The%20Five%20Levels%20of%20AI%20Enablement%2C%20audience%20at%20Level%201%20%28Done%20by%20you%29#check-my-dates"');
        expect($html)->toContain('data-problem="AI enablement and adoption: our people do not use it well"');
        expect($html)->toContain('data-topic="The Five Levels of AI Enablement, audience at Level 5 (Done ahead of you)"');
        expect($html)->toContain('level%20not%20chosen#check-my-dates');
        expect($html)->toContain('Not sure yet? Go to the form.');
    });

    it('keeps the ladder, the levels, and the shares out of the first screen, and sets reach and promotion right after it', function () {
        $html = crispyBookingRender();
        preg_match('#<section class="hero".*?</section>#s', $html, $hero);
        preg_match('#<section id="reach".*?</section>#s', $html, $reach);

        expect($hero[0])->not->toContain('Done by you');
        expect($hero[0])->not->toContain('#levels');
        expect(strpos($html, 'id="reach"'))->toBeGreaterThan(strpos($html, 'class="hero"'));
        expect(strpos($html, 'id="reach"'))->toBeLessThan(strpos($html, 'id="rooms"'));
        expect(crispyBookingText($reach[0]))->toContain('About 292K newsletter subscribers (October 2026) 49,269 LinkedIn followers (October 2026)');
        expect(crispyBookingText($reach[0]))->toContain('A full-fee keynote includes promotion of your event: newsletter inclusion, social media posts, and a promotional video clip.');
    });

    it('opens with the question and the five problems on the first screen, and puts the level finder after the proof', function () {
        $html = crispyBookingRender();

        expect(strpos($html, 'class="pk"'))->toBeGreaterThan(strpos($html, 'class="hero"'));
        expect(strpos($html, 'class="pk"'))->toBeLessThan(strpos($html, 'id="rooms"'));
        expect(strpos($html, 'id="levels"'))->toBeGreaterThan(strpos($html, 'id="also"'));
        expect(strpos($html, 'id="levels"'))->toBeLessThan(strpos($html, 'id="fees"'));
        expect(substr_count($html, 'class="pk__item"'))->toBe(5);
        expect(strpos(substr($html, 0, (int) strpos($html, 'id="levels"')), 'Done by you'))->toBeFalse();
    });

    it('says the lead keynote sentence once on the page, and keeps the one h1', function () {
        $html = crispyBookingRender();

        expect(substr_count($html, 'names who does the work at each level.'))->toBe(1);
        expect(substr_count($html, '<h1'))->toBe(1);
    });

    it('sets the booking steps in a real ordered list of nine steps', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('<ol class="bp-slip"');
        expect(preg_match('#<ol class="bp-slip".*?</ol>#s', $html, $match))->toBe(1);
        expect(substr_count($match[0], '<li>'))->toBe(9);
    });

    it('asks seven planner questions', function () {
        $html = crispyBookingRender();

        preg_match('#<div class="bp-faq__text">(.*?)</div>#s', $html, $match);
        // One answer is open text. Six fold behind a details element. It carries the open attribute, so it reads without script, and the script closes it on a phone.
        expect(substr_count($match[1], '<p><strong>'))->toBe(1);
        expect(substr_count($match[1], '<details data-wide-open open><summary>'))->toBe(6);
    });

    it('prints the LPA letter with its date and no quotation marks', function () {
        $html = crispyBookingRender();
        $text = crispyBookingText($html);

        expect($text)->toContain('Clark Mulligan President, LPA, April 2024');
        expect($text)->toContain('you gave them inspiration and ideas as to how they can incorporate AI into their companies moving forward.');
        preg_match('#<blockquote>(.*?)</blockquote>#s', $html, $match);
        expect(crispyBookingText($match[1]))->not->toMatch('/["“”]/');
    });

    it('prints the form shortcode with the form id option', function () {
        expect(crispyBookingRender())->toContain('[gravityforms id="5" title="false" description="false" ajax="true"]');
        expect(crispyBookingRender(['form_id' => '9']))->toContain('[gravityforms id="9"');
    });

    it('credits every photograph and carries the as-of date', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('Photo: Christopher S. Penn');
        expect($html)->toContain('data-facts-as-of="2026-10-26"');
        expect(crispyBookingRender(['facts_as_of' => '2027-01-15']))->toContain('data-facts-as-of="2027-01-15"');
    });

    it('shows at least four real photographs with a width, a height, and alt text', function () {
        // The silent loop of the reel takes the place of the stage photograph in the room list.
        $html = crispyBookingRender(['reel_loop_url' => 'https://example.test/loop.mp4']);

        preg_match_all('#<img [^>]*>#', $html, $images);
        expect(count($images[0]))->toBe(5);
        foreach ($images[0] as $img) {
            expect($img)->toMatch('/ width="\d+" height="\d+" alt="[^"]+"/');
        }
    });

    it('puts the hero quote word for word from the LPA letter, with its repeat booking in the same block', function () {
        $html   = crispyBookingRender();
        preg_match('#<section class="hero".*?</section>#s', $html, $hero);

        expect(implode(' ', BookingPage::letter_paragraphs()))->toContain(\CrispyTheme\Booking\Hero::QUOTE);
        expect($hero[0])->toContain('<blockquote><p>' . \CrispyTheme\Booking\Hero::QUOTE . '</p></blockquote>');
        expect(crispyBookingText($hero[0]))->toContain('Clark Mulligan, President, LPA, April 2024.');
        expect(crispyBookingText($hero[0]))->toMatch('/booked[^.]{0,60}again/');
    });

    it('shows one photograph of Penn beside the LPA letter, and none from a conference lanyard frame', function () {
        $html = crispyBookingRender();
        preg_match('#<section id="letter".*?</section>#s', $html, $match);

        expect($match[0])->toContain('<figure class="letter__pic"');
        expect($match[0])->toContain('level.jpg');
        expect($html)->not->toContain('support-04');
        expect(array_keys(BookingPage::manifest()))->not->toContain('letter');
    });

    it('uses photo 14 as the hero, with the MAICON name only, and names no other event', function () {
        $html = crispyBookingRender();
        preg_match('#<section class="hero".*?</figure>#s', $html, $match);

        expect($match[0])->toContain('beside the MAICON sign');
        expect($match[0])->toContain('hero-43.jpg');
        // Social Media Marketing World may show as a name in the list of older stages. No photograph of it shows.
        expect($html)->not->toContain('hero-01');
        expect($html)->not->toContain('support-02');
        expect($html)->not->toContain('SMMW');
    });

    it('shows each photograph once', function () {
        $html = crispyBookingRender(['reel_loop_url' => 'https://example.test/loop.mp4']);
        preg_match_all('#<img src="([^"]+)"#', $html, $sources);
        $files = array_map('basename', $sources[1]);

        // The hero lists two sizes of one photograph (hero-2400 and, in srcset, hero-1200).
        expect(count($files))->toBe(count(array_unique($files)));
    });

    it('never uses the two photographs Penn left out', function () {
        $html = crispyBookingRender();

        expect($html)->not->toContain('support-05');
        expect($html)->not->toContain('support-06');
        expect(array_keys(BookingPage::manifest()))->not->toContain('support-05', 'support-06');
    });
});

describe('BookingPage reads Theme Options', function () {
    it('shows the posted fees, and the FAQ repeats the same options', function () {
        $html = crispyBookingRender();
        $text = crispyBookingText($html);

        expect($text)->toContain('Keynotes from $25,000');
        expect($text)->toContain('Workshops from $25,000');
        expect($text)->toContain('Keynotes from $25,000. Workshops from $25,000. Travel is billed at cost pursuant to contract requirements.');
        expect($text)->not->toContain('$35,000');
        expect($text)->not->toContain('$50,000');
        expect($text)->not->toContain('Executive and investor');
    });

    it('changes every place that states a fee', function () {
        $text = crispyBookingText(crispyBookingRender([
            'fee_keynote_from'  => '30000',
            'fee_workshop_from' => '28000',
        ]));

        expect($text)->toContain('Keynotes from $30,000');
        expect($text)->toContain('Keynotes from $30,000. Workshops from $28,000. Travel is billed at cost pursuant to contract requirements.');
        expect($text)->toContain('Workshops from $28,000');
        expect($text)->not->toContain('$25,000');
    });

    it('states the deposit, the cancellation schedule, and the balance date', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->toContain('Deposit 50% Due at signature.');
        expect($text)->toContain('More than 6 months: 75% refund. 3 to 6 months: 50%. Under 3 months: none. Booked travel is never refunded.');
        expect($text)->toContain('Balance 30 days before the event');
        expect($text)->toContain('The deposit is 50 percent of the fee.');
    });

    it('follows a changed schedule and a changed balance date', function () {
        $text = crispyBookingText(crispyBookingRender([
            'deposit_percent'      => '40',
            'refund_over_6_months' => '80',
            'refund_3_to_6_months' => '40',
            'refund_under_3_months' => '10',
            'balance_days'         => '45',
        ]));

        expect($text)->toContain('Deposit 40%');
        expect($text)->toContain('More than 6 months: 80% refund. 3 to 6 months: 40%. Under 3 months: 10%.');
        expect($text)->toContain('Balance 45 days before the event');
        expect($text)->toContain('45 days before the event The balance is due.');
        expect($text)->toContain('The deposit is 40 percent of the fee.');
    });

    it('states the travel and flight terms', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->toContain('Transportation, lodging, and meals You pay Pre-pay them, or reimburse my team on receipts within 30 days of the event.');
        expect($text)->toContain('Flights Business class where offered If a route has no business class, the highest class offered.');
        expect($text)->toContain('International flights Lie-flat business class The contract names the seat.');
        expect($text)->toContain('Travel term In writing In the contract before I hold a date.');
    });

    it('states what a full-fee booking includes', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->toContain('A full-fee booking includes');
        expect($text)->toContain('Full fee means the stated fee, with no discount and no negotiation.');
        expect($text)->toContain('Prep call 30 minutes');
        expect($text)->toContain('Event promotion Full-fee keynote Newsletter inclusion, social media posts, and a promotional video clip. This covers a full-fee keynote only.');
        expect($text)->toContain('Transcript eBook Keynote or workshop');
        expect($text)->toContain('Transcripts, an interactive AI instance, slide decks, session recordings, pre-event customization, and custom data for workshops.');
    });

    it('writes the call lengths and credentials from options', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->toContain('A 30-minute fit call with Trust Insights business development.');
        expect($text)->toContain('A 30-minute prep call is included.');
        expect($text)->toContain('I am an eight-time IBM Champion, and 2025 was the final year. Author of 14 published books, 19 editions.');
        expect($text)->toContain('49,269 LinkedIn followers (October 2026)');

        $changed = crispyBookingText(crispyBookingRender(['fit_call_minutes' => '20', 'book_titles' => '15']));
        expect($changed)->toContain('A 20-minute fit call');
        expect($changed)->toContain('Author of 15 published books');
    });

    it('names the 15 companies as text and sets a curly apostrophe', function () {
        $html = crispyBookingRender();

        expect(substr_count($html, '<li class="also__team">'))->toBe(15);
        expect($html)->toContain('<li class="also__team">McDonald’s</li>');
    });

    it('links each stage to its source', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('href="https://spinsucks.com/communication/dreamforce-a-must-attend-event-for-marketers-and-pr-professionals/"');
        expect($html)->toContain('href="https://www.youtube.com/watch?v=U52b1O-059Y"');
    });

    it('hides the reel control until the reel option is set', function () {
        expect(crispyBookingRender())->not->toContain('Play the reel');

        $html = crispyBookingRender(['reel_url' => 'https://example.com/reel.mp4', 'reel_poster' => 'https://example.com/poster.jpg']);
        expect($html)->toContain('Play the reel');
        expect($html)->toContain('src="https://example.com/reel.mp4"');
        expect($html)->toContain('poster="https://example.com/poster.jpg"');
    });

    it('plays a YouTube reel in a lazy no-cookie frame, with no video element and no YouTube request before the dialog opens', function () {
        $html = crispyBookingRender(['reel_url' => 'https://www.youtube.com/watch?v=w7uxV3PjGXA']);

        expect($html)->toContain('Play the reel');
        expect($html)->toContain('data-src="https://www.youtube-nocookie.com/embed/w7uxV3PjGXA?rel=0');
        expect($html)->not->toContain('<iframe title="Demo reel" src=');
        expect($html)->not->toContain('<video controls');
    });

    it('reads the video id of YouTube watch, short, and embed links only', function () {
        expect(BookingPage::youtube_id('https://www.youtube.com/watch?v=w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://www.youtube.com/watch?feature=share&v=w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://youtu.be/w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://www.youtube.com/embed/w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://www.youtube.com/live/w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://www.youtube-nocookie.com/embed/w7uxV3PjGXA'))->toBe('w7uxV3PjGXA');
        expect(BookingPage::youtube_id('https://example.com/reel.mp4'))->toBe('');
        expect(BookingPage::youtube_id('https://evil.example/https://www.youtube.com/watch?v=w7uxV3PjGXA'))->toBe('');
        expect(BookingPage::youtube_id('https://www.youtube.com/watch?v=w7uxV3PjGXAextra'))->toBe('');
    });

    it('moves the topic links when the page path option changes', function () {
        $html = crispyBookingRender(['page_path' => '/book/']);

        expect($html)->toContain('href="/book/?problem=');
    });
});

describe('BookingPage claims', function () {
    it('makes none of the claims Penn removed or left unnamed', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->not->toContain('500+');
        expect($text)->not->toContain('John Wall');
        expect($text)->not->toContain('25 countries');
        expect($text)->not->toContain('98%');
        expect($text)->not->toContain('294,000');
        expect($text)->not->toContain('—');
    });

    it('speaks as Penn and names Trust Insights business development', function () {
        $text = crispyBookingText(crispyBookingRender());

        expect($text)->toContain('AI builder, keynote speaker. I build what I speak about.');
        expect($text)->toContain('Trust Insights staff reply within one business day (Eastern Time).');
        expect($text)->toContain('The form goes to my team at Trust Insights, and they answer within one business day, Eastern Time.');
    });
});

describe('BookingPage blocks', function () {
    it('wraps the page in one dynamic block so the options load only on this page', function () {
        expect(BookingPage::content())->toBe('<!-- wp:crispytheme/booking-page /-->');
    });

    it('registers the block on init and renders it with the form shortcode run', function () {
        $this->setupHooks();
        $page = new BookingPage();
        $page->init();
        $this->assertActionRegistered('init', [$page, 'register_block']);

        Functions\expect('register_block_type')->once()->with('crispytheme/booking-page', Mockery::type('array'));
        $page->register_block();

        crispyBookingRender();
        Functions\expect('do_shortcode')->once()->andReturnUsing(static fn(string $html): string => $html);
        expect(BookingPage::render_block())->toContain('<main id="main" class="bp bp-book"');
    });

    it('tells the booking page by its path option', function () {
        Functions\when('get_option')->justReturn('');
        Functions\expect('is_page')->once()->with('public-speaking')->andReturn(true);

        expect(BookingPage::is_booking_page())->toBeTrue();
    });
});

describe('BookingPage folds and phone order', function () {
    it('folds the longer lists behind native details elements, so they read without script', function () {
        $html = crispyBookingRender();

        expect(substr_count($html, '<details'))->toBe(12);
        foreach (['The eight older stages', 'Teams I have worked with', 'The five levels', 'Deposit, cancellation, balance, and travel term', 'Travel', 'A full-fee booking includes'] as $summary) {
            expect($html)->toContain('<summary>' . $summary . '</summary>');
        }
        // The text stays in the markup, so a closed fold hides nothing from a reader or a crawler.
        expect($html)->toContain('Booked travel is never refunded.');
        expect($html)->toContain('MarketingProfs B2B Forum');
    });

    it('marks the main element for the booking page and sets the reply promise under the promotion line', function () {
        $html = crispyBookingRender();

        expect($html)->toContain('<main id="main" class="bp bp-book"');
        expect(substr_count($html, 'class="bp-sec reach bp-alt"'))->toBe(1);
        expect($html)->toContain('<p class="reach__reply">Trust Insights staff reply within one business day (Eastern Time).</p>');
    });
});
