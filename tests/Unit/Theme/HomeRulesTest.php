<?php
/**
 * Static guards for the home page assets, template, and pattern.
 *
 * The rendered result is measured with inspect.mjs, src/inspect_schemes.sh, and src/home_check.mjs in the
 * research project. These tests keep the house rules of design-taste-frontend and the plan of
 * 2026-10-24 (Phase 3) in the source.
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Theme\Assets;

beforeEach(function () {
    $this->root = dirname(__DIR__, 3);
    $this->css  = (string) file_get_contents($this->root . '/assets/css/home-page.css');
});

describe('home page stylesheet', function () {
    it('uses no gradients, no resting shadows, no monospace, and no uppercase labels', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->not->toContain('gradient(');
        expect($code)->not->toMatch('/box-shadow:(?!\s*none)/');
        expect($code)->not->toContain('monospace');
        expect($code)->not->toContain('text-transform: uppercase');
        expect($code)->not->toMatch('/border(-top|-bottom|-left|-right)?:\s*\d/');
    });

    it('adds no new colour: it reads the booking tokens only, and never the cyan accent', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->not->toMatch('/#[0-9a-fA-F]{3,8}\b/');
        expect($code)->not->toContain('--bp-accent');
    });

    it('moves only transform and opacity: it sets no transition of its own', function () {
        preg_match_all('/transition:\s*([^;]+);/', $this->css, $matches);

        foreach ($matches[1] as $transition) {
            expect($transition)->not->toMatch('/\b(width|height|margin|padding|top|left)\b/');
        }
    });

    it('sets the headline from the page token, which holds two lines at every width', function () {
        $booking = (string) file_get_contents($this->root . '/assets/css/booking-page.css');

        // The token grows with the screen up to 96 px (4.6 vw, so the headline leads the first screen), and with the content width below 1000 px.
        expect($booking)->toContain('--t-h1: clamp(44px, 4.6vw, 96px);');
        expect($booking)->toContain('--t-h1: clamp(26px, calc((100vw - 2 * var(--bp-g)) * 0.068), 56px);');
        expect($booking)->toContain('letter-spacing: -0.025em;');
    });

    it('keeps the header booking button, because the first screen holds five problems and one of them is not enough', function () {
        expect($this->css)->not->toMatch('/\.site-header__cta\s*\{[^}]*display:\s*none/');
    });
});

describe('home page template and pattern', function () {
    it('uses the site header and the new pattern only, and none of the removed blocks', function () {
        $template = (string) file_get_contents($this->root . '/templates/front-page.html');

        expect($template)->toContain('"slug":"header"');
        expect($template)->toContain('crispytheme/home-page');
        foreach (['newsletter-hero', 'metrics-badge', 'homepage-booking', 'homepage-clients', 'audience-routing', 'booking-cta', '"slug":"footer"'] as $removed) {
            expect($template)->not->toContain($removed);
        }
    });

    it('registers the pattern from the dynamic block', function () {
        $pattern = (string) file_get_contents($this->root . '/block-patterns/home-page.php');

        expect($pattern)->toContain("'crispytheme/home-page'");
        expect($pattern)->toContain('HomePage::content()');
    });

    it('keeps the approved headline and subhead as the defaults of the options screen, so a save never brings the old text back', function () {
        $options = (string) file_get_contents($this->root . '/src/Admin/OptionsPage.php');

        expect($options)->toContain('HomePage::HEADLINE');
        expect($options)->toContain('HomePage::SUBHEAD');
        expect($options)->not->toContain('Equipping Global');
        expect($options)->not->toContain('shows enterprise teams');
        expect(\CrispyTheme\Booking\HomePage::HEADLINE)->toBe('Helping global organizations win with AI since 2013');
    });

    it('registers the home block when the theme starts', function () {
        expect((string) file_get_contents($this->root . '/functions.php'))->toContain('new Booking\HomePage()');
    });
});

describe('home page assets', function () {
    beforeEach(function () {
        Functions\when('is_page')->justReturn(false);
        Functions\when('is_singular')->justReturn(false);
        Functions\when('get_option')->alias(static fn(string $name, mixed $default = false): mixed => $default);
    });

    it('loads the booking design system and the home stylesheet on the front page only', function () {
        $assets = (string) file_get_contents($this->root . '/src/Theme/Assets.php');

        expect($assets)->toContain('HomePage::is_home_page()');
        expect($assets)->toContain("'crispy-theme-home'");
        expect($assets)->toContain('/assets/css/home-page.css');
        expect($assets)->toContain("[ 'crispy-theme-booking' ]");
        expect(Assets::class)->toBeString();
    });

    it('tells the front page apart from other pages', function () {
        Functions\when('is_front_page')->justReturn(true);
        expect(\CrispyTheme\Booking\HomePage::is_home_page())->toBeTrue();

        Functions\when('is_front_page')->justReturn(false);
        expect(\CrispyTheme\Booking\HomePage::is_home_page())->toBeFalse();
    });
});
