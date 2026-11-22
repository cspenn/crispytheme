<?php
/**
 * HomePage Unit Tests.
 *
 * The home page has one job: send a planner to the booking page. Each test renders the page with chosen
 * option values and reads the HTML. The approved texts of 2026-10-25 and the facts of the booking page
 * (BookingFacts) must show, and everything that Penn removed must stay out.
 *
 * @package CrispyTheme\Tests\Unit\Booking
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\BookingFacts;
use CrispyTheme\Booking\BookingPage;
use CrispyTheme\Booking\HomePage;

/**
 * Render the home page with chosen option values.
 *
 * @param array<string, string> $options Option values by full option name or fact key.
 */
function crispyHomeRender(array $options = []): string
{
    $named = [];
    foreach ($options as $key => $value) {
        $named[str_starts_with($key, 'crispytheme_') ? $key : BookingFacts::option_name($key)] = $value;
    }

    Functions\when('get_option')->alias(
        static fn(string $name, mixed $default = false): mixed => $named[$name] ?? $default
    );
    Functions\when('esc_url')->alias(static fn(string $url): string => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    Functions\when('wp_date')->justReturn('2026');
    // Another test file may load inc/template-tags.php. Then the real functions run on the option defaults.
    $real = function_exists('crispytheme_get_subscriber_count')
        && str_ends_with((string) (new ReflectionFunction('crispytheme_get_subscriber_count'))->getFileName(), 'template-tags.php');
    if ($real) {
        Functions\when('get_transient')->justReturn(false);
    } else {
        Functions\when('crispytheme_get_subscriber_count')->justReturn('About 292K');
        Functions\when('crispytheme_get_subscriber_as_of')->justReturn('October 2026');
    }

    return HomePage::html();
}

/** Return the visible text of an HTML string. */
function crispyHomeText(string $html): string
{
    $spaced = (string) preg_replace('/<[^>]+>/', ' ', $html);

    return trim((string) preg_replace('/\s+/', ' ', html_entity_decode($spaced, ENT_QUOTES, 'UTF-8')));
}

describe('HomePage first screen', function () {
    it('has one h1 with the approved headline, and the approved subhead in the speakable class', function () {
        $html = crispyHomeRender();

        expect(substr_count($html, '<h1'))->toBe(1);
        expect($html)->toContain('>Helping global organizations win with AI since 2013</h1>');
        expect($html)->toContain('<p class="hero__sub speakable-summary">Christopher S. Penn is an AI builder and keynote speaker. His keynotes and workshops teach teams the Five Levels of AI Enablement.</p>');
    });

    it('lets the options change the headline and the subhead, and falls back to the approved text when blank', function () {
        $html = crispyHomeRender(['crispytheme_hero_headline' => 'A new headline', 'crispytheme_hero_subheadline' => 'A new subhead.']);

        expect($html)->toContain('>A new headline</h1>');
        expect($html)->toContain('>A new subhead.</p>');
        expect(crispyHomeRender(['crispytheme_hero_headline' => '  ']))->toContain(HomePage::HEADLINE);
    });

    it('has one primary action, Check my dates, and its address comes from the booking page path', function () {
        $html = crispyHomeRender();
        preg_match_all('#<a class="bp-btn" href="([^"]+)">([^<]+)</a>#', $html, $buttons);

        // Two links to the form (the promotion line and the close), then the newsletter button of the footer.
        expect($buttons[2])->toBe(['Check my dates', 'Check my dates', 'Subscribe free']);
        expect($buttons[1])->toBe(['/public-speaking/#check-my-dates', '/public-speaking/#check-my-dates', '/newsletter/']);
        expect($html)->toContain('<button class="bp-btn" type="submit">Check my dates</button>');
        expect(crispyHomeRender(['page_path' => '/book/']))->toContain('href="/book/#check-my-dates"');
    });

    it('shows one picture first: the whole MAICON frame in 4:3, high priority, with its size and alt text', function () {
        $html = crispyHomeRender();
        preg_match('#<section class="hero".*?</section>#s', $html, $match);

        expect($match[0])->toContain('<figure class="hero__pic"><img src="');
        expect($match[0])->toContain('hero-43.jpg');
        expect($match[0])->toContain('fetchpriority="high"');
        expect($match[0])->toMatch('/ width="1800" height="1350" alt="Christopher Penn in a grey suit speaks on a lit stage beside the MAICON sign\."/');
    });

    it('keeps the reply promise and no eyebrow above the headline', function () {
        $html = crispyHomeRender();

        expect($html)->toContain('Trust Insights staff reply within one business day (Eastern Time).');
        expect($html)->not->toContain('eyebrow');
        expect(strpos($html, '<h1'))->toBeLessThan(strpos($html, 'hero__sub'));
    });

    it('puts one real sentence of the LPA letter in the hero, with its source and the repeat booking in the same block', function () {
        $html = crispyHomeRender();
        preg_match('#<section class="hero".*?</section>#s', $html, $hero);

        expect(implode(' ', BookingPage::letter_paragraphs()))->toContain(\CrispyTheme\Booking\Hero::QUOTE);
        expect($hero[0])->toContain('<div class="hero__band"><div class="bp-in hero__band-in"><p class="hero__sub speakable-summary">');
        expect($hero[0])->toContain('<figure class="hero__quote"><blockquote><p>' . \CrispyTheme\Booking\Hero::QUOTE . '</p></blockquote>');
        expect(crispyHomeText($hero[0]))->toContain('Clark Mulligan, President, LPA, April 2024. LPA booked Christopher Penn again in September 2026.');
        expect(str_word_count(\CrispyTheme\Booking\Hero::QUOTE))->toBeLessThanOrEqual(25);
    });

    it('puts the subhead and the quote in a band right under the hero, and keeps the first screen to the headline, the menu, and the photograph', function () {
        $html = crispyHomeRender();
        preg_match('#<section class="hero".*?</section>#s', $html, $hero);

        expect(substr_count($hero[0], '<h1'))->toBe(1);
        expect(strpos($hero[0], 'hero__grid'))->toBeLessThan(strpos($hero[0], 'hero__band'));
        expect(strpos($hero[0], 'hero__band'))->toBeLessThan(strpos($hero[0], 'hero__sub'));
        expect(strpos($hero[0], 'hero__sub'))->toBeLessThan(strpos($hero[0], 'hero__quote'));
    });

    it('keeps the fees link beside the button, and leaves the not-listed choice to the first form field', function () {
        $html = crispyHomeRender();

        // Round 4 cut the "My problem is not here" line from the first screen so the headline, the five names, and the button fit above the fold.
        // A planner whose problem is not listed presses the button, and the first form field offers "Something else, or not sure yet".
        expect($html)->not->toContain('pk__other');
        expect(\CrispyTheme\Booking\ProblemPicker::choices())->toContain(\CrispyTheme\Booking\ProblemPicker::OTHER);
        expect($html)->toContain('<a class="bp-link" href="/public-speaking/#fees">Fees and terms</a>');
    });

    it('shows the reel control only when the reel option holds a file', function () {
        $off = crispyHomeRender();
        $on  = crispyHomeRender(['reel_url' => 'https://example.test/reel.mp4', 'reel_poster' => 'https://example.test/poster.jpg']);

        expect($off)->not->toContain('data-reel-open');
        expect($off)->not->toContain('<dialog');
        expect($on)->toContain('data-reel-open');
        expect($on)->toContain('src="https://example.test/reel.mp4"');
        expect($on)->toContain('poster="https://example.test/poster.jpg"');
    });
});

describe('HomePage talk section: the Five Levels', function () {
    it('lists the five levels in a real ordered list, each rung a link to the form with the level filled in', function () {
        $html = crispyHomeRender();
        preg_match('#<ol class="levels__list".*?</ol>#s', $html, $match);

        expect(substr_count($match[0], '<li '))->toBe(5);
        expect($match[0])->not->toMatch('/\b(0[1-9]|Step \d)\b/');
        foreach (['Done by you', 'Done with you', 'Done for you', 'Done without you', 'Done ahead of you'] as $name) {
            expect($match[0])->toContain($name);
        }
        expect($match[0])->toContain('href="/public-speaking/?problem=AI%20enablement%20and%20adoption%3A%20our%20people%20do%20not%20use%20it%20well&amp;topic=The%20Five%20Levels%20of%20AI%20Enablement%2C%20audience%20at%20Level%203%20%28Done%20for%20you%29#check-my-dates"');
    });

    it('states the share of the work from the canonical slide, and the bar shrinks with each level', function () {
        $html = crispyHomeRender();

        foreach (['You do 100% of the work', 'You do 60 to 70% of the work', 'You do 10 to 20% of the work', 'You do 1 to 5% of the work', 'You do 0% of the work'] as $line) {
            expect($html)->toContain($line);
        }
        preg_match_all('/style="--bar:(\d+)"/', $html, $bars);
        expect($bars[1])->toBe(['100', '70', '20', '5', '0']);
    });

    it('says that a level cannot be skipped', function () {
        expect(crispyHomeText(crispyHomeRender()))->toContain('Each level builds on the one before it. An organization cannot skip a level.');
    });

    it('comes after the proof, and nothing before it shows a level, a ladder, or a share', function () {
        $html   = crispyHomeRender();
        $before = substr($html, 0, (int) strpos($html, 'id="levels"'));

        expect(strpos($html, 'id="levels"'))->toBeGreaterThan(strpos($html, 'id="also"'));
        foreach (['Done by you', 'Done with you', 'Level 1', 'of the work', 'hp-rung', 'bp-ladder'] as $word) {
            expect($before)->not->toContain($word);
        }
    });
});

describe('HomePage proof', function () {
    it('prints the first paragraph of the LPA letter whole, with its date and a link to the full letter', function () {
        $html = crispyHomeRender();
        $text = crispyHomeText($html);

        expect($text)->toContain(BookingPage::letter_paragraphs()[0]);
        expect($text)->toContain('as of right now');
        expect($text)->toContain('Clark Mulligan President, LPA, April 2024');
        expect($html)->toContain('href="/public-speaking/#letter"');
        preg_match('#<blockquote>(.*?)</blockquote>#s', $html, $match);
        expect(crispyHomeText($match[1]))->not->toMatch('/["“”]/');
    });

    it('takes the letter date from the quote date option', function () {
        expect(crispyHomeText(crispyHomeRender(['quote_date' => 'May 2027'])))->toContain('President, LPA, May 2027');
    });

    it('lists the eight older stages apart, and links the two with an independent source', function () {
        $html = crispyHomeRender();
        preg_match('#<ul class="also__list".*?</ul>#s', $html, $match);

        expect(substr_count($match[0], '<li class="also__stage">'))->toBe(8);
        foreach (['IBM Think', 'IBM World of Watson, 2016', 'Dreamforce, ', 'INBOUND, ', ' and 2024', 'Social Media Marketing World', 'Content Marketing World', 'MarketingProfs B2B Forum', 'MAICON, 2019 opening keynote and 2026'] as $stage) {
            expect($match[0])->toContain($stage);
        }
        expect($match[0])->toContain('https://spinsucks.com/communication/dreamforce-a-must-attend-event-for-marketers-and-pr-professionals/');
        expect($match[0])->toContain('https://www.youtube.com/watch?v=U52b1O-059Y');
        expect($html)->toContain('<section id="also" class="bp-sec also bp-alt" aria-labelledby="bp-also-h" data-also>');
    });

    it('keeps the old stages out of the recent rooms and gives the letter its rebooking line', function () {
        $html = crispyHomeRender();
        preg_match('#<ol class="rooms__list".*?</ol>#s', $html, $rooms);

        expect($rooms[0])->not->toMatch('/Dreamforce|INBOUND|World of Watson|2012|2016/');
        expect(crispyHomeText($html))->toContain('LPA booked Christopher Penn again in September 2026.');
    });

    it('lists the fifteen company names as plain text and follows the option', function () {
        $text = crispyHomeText(crispyHomeRender());

        expect($text)->toContain('Teams Christopher has worked with');
        $names = ['Pfizer', 'McDonald’s', 'T-Mobile', 'Google', 'Cisco Investments', 'JLL', 'McKinsey', 'Twitter', 'Okta', 'Elanco', 'Fresenius', 'Sprinklr', 'Demandbase', 'CommScope', 'Gentex'];
        $html  = crispyHomeRender();
        expect(substr_count($html, '<li class="also__team">'))->toBe(15);
        foreach ($names as $name) {
            expect($html)->toContain('<li class="also__team">' . $name . '</li>');
        }
        expect(crispyHomeRender(['client_names' => 'Alpha, Beta']))->toContain('<li class="also__team">Alpha</li><li class="also__team">Beta</li>');
    });

    it('keeps the approved Fortune 500 sentence word for word', function () {
        expect(crispyHomeText(crispyHomeRender()))->toContain('Keynotes and workshops that turn AI into your audience’s competitive advantage. Christopher has worked with Fortune 500 companies including Pfizer, T-Mobile, and McDonald’s.');
    });
});

describe('HomePage newsletter and footer', function () {
    it('puts one quiet newsletter section after the proof, with the approved count line and one link', function () {
        $html = crispyHomeRender();
        $text = crispyHomeText($html);

        expect($text)->toContain('About 292K subscribers (October 2026)');
        expect(strpos($html, 'foot__news'))->toBeGreaterThan(strpos($html, 'id="also"'));
        expect(strpos($html, 'foot__news'))->toBeGreaterThan(strpos($html, 'class="close"'));
        preg_match('#<div class="foot__news">.*?</div>#s', $html, $match);
        expect($match[0])->toContain('<a class="bp-btn" href="/newsletter/">Subscribe free</a>');
        expect(substr_count($match[0], 'href='))->toBe(1);
    });

    it('ends with the quiet footer and the photo credit', function () {
        $html = crispyHomeRender();

        expect($html)->toContain('<footer class="bp foot bp-alt">');
        expect($html)->toContain('Photo: Christopher S. Penn');
        expect($html)->toContain('data-facts-as-of="2026-10-26"');
    });
});

describe('HomePage footer links and the one booking address', function () {
    it('keeps the links of the original footer: Blog, LinkedIn, Trust Insights, and RSS, and the privacy page', function () {
        $html = crispyHomeRender();

        foreach (['href="/blog/"', 'href="https://www.linkedin.com/in/christopherspenn/"', 'href="https://www.trustinsights.ai"', 'href="/feed/"', 'href="/privacy/"'] as $link) {
            expect($html)->toContain($link);
        }
        expect($html)->not->toContain('/terms');
        expect($html)->not->toContain('/privacy-policy');
    });

    it('builds the booking form address in one place', function () {
        crispyHomeRender(); // sets the option stubs
        expect(BookingFacts::form_url())->toBe('/public-speaking/#check-my-dates');

        foreach (glob(dirname(__DIR__, 3) . '/src/Booking/*.php') as $file) {
            if (basename($file) === 'BookingFacts.php') {
                continue;
            }
            expect(file_get_contents($file))->not->toMatch("/page_path'\\s*\\)\\s*\\.\\s*'#check-my-dates'/", basename($file));
        }
    });

    it('makes the booking address absolute from the root, whatever slashes the saved page path has', function () {
        foreach (['public-speaking', '/public-speaking', 'public-speaking/', '/public-speaking/'] as $saved) {
            crispyHomeRender(['page_path' => $saved]);

            expect(BookingFacts::form_url())->toBe('/public-speaking/#check-my-dates');
        }
    });

    it('points the home buttons at the one booking form address', function () {
        $html = crispyHomeRender();

        expect($html)->toContain('href="' . BookingFacts::form_url() . '"');
    });
});

describe('HomePage structure and removed content', function () {
    it('gives every section a heading and uses the home root class so the booking script runs', function () {
        $html = crispyHomeRender();

        expect($html)->toContain('<main id="main" class="bp bp-home"');
        expect(substr_count($html, '<h2'))->toBe(8);
        foreach (['What is your audience’s main problem?', 'Promotion comes with the keynote', 'Recent rooms', 'What LPA wrote', 'Also spoken at', 'The Five Levels of AI Enablement', 'Bring Christopher to your stage', 'Almost Timely newsletter'] as $heading) {
            expect($html)->toContain($heading);
        }
    });

    it('shows five real photographs with a size and alt text, each file once, and no uncleared event', function () {
        // The silent loop of the reel takes the place of the stage photograph in the room list.
        $html = crispyHomeRender(['reel_loop_url' => 'https://example.test/loop.mp4']);

        preg_match_all('#<img [^>]*>#', $html, $images);
        expect(count($images[0]))->toBe(5);
        foreach ($images[0] as $img) {
            expect($img)->toMatch('/ width="\d+" height="\d+" alt="[^"]+"/');
        }
        preg_match_all('#<img src="([^"]+)"#', $html, $sources);
        $files = array_map('basename', $sources[1]);
        expect(count($files))->toBe(count(array_unique($files)));
        // Social Media Marketing World shows as a name in the list of older stages. No photograph of it shows.
        expect($html)->not->toContain('hero-01');
        expect($html)->not->toContain('support-02');
        expect($html)->not->toContain('SMMW');
        expect($html)->not->toContain('hero-2400');
        expect($html)->not->toContain('support-04');
    });

    it('has at least sixteen entrance parts', function () {
        expect(substr_count(crispyHomeRender(), 'data-reveal'))->toBeGreaterThanOrEqual(16);
    });

    it('leaves out everything the plan removed and every claim Penn took out', function () {
        $text = crispyHomeText(crispyHomeRender());

        foreach (['View Speaker Bureau', 'Find your path', 'For event planners', 'For enterprise leaders', 'For technical practitioners', '500+', '98%', '294,000', '25 countries', 'Bestselling', 'over two dozen', 'John Wall'] as $banned) {
            expect($text)->not->toContain($banned);
        }
    });

    it('states no figure that has no source: only the levels, the dates, the shares, and the reach counts', function () {
        $text = crispyHomeText(crispyHomeRender());

        preg_match_all('/\d[\d,.]*K?%?/', $text, $numbers);
        // Each figure has a source in docs/site-facts.yaml: the start year, the levels and shares, the room years, the reach counts.
        $allowed = ['2013', '500', '1', '2', '3', '4', '5', '2012', '2016', '2019', '2025', '2026', '2024', '292K', '49,269', '14', '19', '100%', '60', '70%', '10', '20%', '5%', '0%'];
        foreach (array_unique($numbers[0]) as $number) {
            expect(in_array(rtrim($number, '.,'), $allowed, true))->toBeTrue("unexpected figure: {$number}");
        }
    });
});
