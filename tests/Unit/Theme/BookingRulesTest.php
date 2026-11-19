<?php
/**
 * Static guards for the booking page assets, template, pattern, and options screen.
 *
 * The rendered result is measured with inspect.mjs, src/inspect_schemes.sh, and src/booking_check.mjs
 * in the research project. These tests keep the house rules of design-taste-frontend in the source.
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Booking\BookingFacts;
use CrispyTheme\Theme\Assets;

beforeEach(function () {
    $this->root = dirname(__DIR__, 3);
    $this->css  = (string) file_get_contents($this->root . '/assets/css/booking-page.css');
    $this->js   = (string) file_get_contents($this->root . '/assets/js/booking-page.js');
});

describe('booking page stylesheet', function () {
    it('uses no gradients, no resting shadows, no monospace, and no uppercase labels', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->not->toContain('gradient(');
        expect($code)->not->toMatch('/box-shadow:(?!\s*none)/');
        expect($code)->not->toContain('monospace');
        expect($code)->not->toContain('text-transform: uppercase');
        expect($code)->not->toContain('letter-spacing: 0.');
    });

    it('keeps the brand: IBM Plex Sans, cyan as a fill only, navy text on cyan', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($this->css)->toContain('--wp--preset--font-family--body');
        expect($this->css)->toContain('--bp-accent: #06b6d4');
        expect($this->css)->toContain('--bp-accent-ink: #0f172a');
        // Cyan marks the action and the chosen state only: the buttons, the bar of the chosen problem, and the form button.
        // Chips, tiles, and bars take navy, white, or a tint (checked in the browser by src/cyan_count.mjs).
        expect(substr_count($code, 'background: var(--bp-accent)'))->toBe(3);
        // Cyan never colours text, a heading, or a rule.
        expect($code)->not->toMatch('/(^|[;{\s])color:\s*var\(--bp-accent\)/');
        expect($code)->not->toMatch('/border[^:]*:[^;]*--bp-accent/');
    });

    it('serves IBM Plex Serif from the theme for headlines, with the licence, and leaves the global heading font alone', function () {
        $theme = json_decode((string) file_get_contents($this->root . '/theme.json'), true);
        $fonts = array_column($theme['settings']['typography']['fontFamilies'], null, 'slug');

        expect($fonts)->toHaveKey('serif');
        expect(array_column($fonts['serif']['fontFace'], 'fontWeight'))->toBe(['500', '600', '700']);
        foreach ($fonts['serif']['fontFace'] as $face) {
            expect(file_exists($this->root . '/' . substr($face['src'][0], 7)))->toBeTrue();
        }
        // The site-wide heading font stays IBM Plex Sans. Only the booking and home pages take the serif.
        expect($fonts['heading']['fontFamily'])->toContain('IBM Plex Sans');
        expect($this->css)->toContain('--bp-serif: var(--wp--preset--font-family--serif');
        expect($this->css)->not->toContain('@font-face');
        expect((string) file_get_contents($this->root . '/assets/fonts/ibm-plex/OFL.txt'))->toContain('SIL OPEN FONT LICENSE Version 1.1');
    });

    it('uses two fields only: deep navy and the light field, with no surface tint and no per-section shade', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->toContain('--bp-field: #0f172a;');
        // Dark mode rests on deep navy, and the alternate band is a lighter navy (2.5 to 1 against deep navy, white text 7.1 to 1).
        expect($code)->toContain('--bp-alt: #36588f;');
        expect($code)->not->toContain('#2d4a79');
        foreach (['#eef2f6', '#161b22', '#172338', '#1d4a73', '#d6e4f2', '#1e293b'] as $old) {
            expect($code)->not->toContain($old);
        }
    });

    it('sets one spacing scale of eight steps and uses it for the section gap and the hero', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        foreach (['--sp-1: 8px;', '--sp-2: 16px;', '--sp-3: 24px;', '--sp-4: 32px;', '--sp-5: 48px;', '--sp-6: 64px;', '--sp-7: 96px;', '--sp-8: 128px;'] as $step) {
            expect($code)->toContain($step);
        }
        expect($code)->toContain('--bp-sec: var(--sp-7);');
        expect($code)->toContain('padding-top: var(--sp-7);');
        // Body text runs 1.6 lines high on a wide screen, and the page gutter is 48 px.
        expect($code)->toContain('line-height: 1.6;');
        expect($code)->toContain('--bp-gap: var(--sp-5);');
    });

    it('gives the reel a full row below 1000 px and sets fees and terms in one column below 900 px', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->toMatch('/@media \(max-width: 999px\) \{\s*\.rooms__in \{\s*row-gap: var\(--sp-5\);\s*\}\s*\.rooms__reel \{\s*grid-column: 1 \/ 13;/');
        expect($code)->toMatch('/@media \(max-width: 899px\) \{\s*\.bp-menu \{\s*columns: 1;/');
        // On a small laptop the quote takes eight columns and the room date sits under the name.
        expect($code)->toMatch('/@media \(min-width: 1000px\) and \(max-width: 1199px\) \{[^@]*\.hero__quote \{\s*grid-column: 5 \/ 13;/s');
    });

    it('paints the whole dark page deep navy and leaves no strip below the footer', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->toMatch('/html\.dark-mode:has\(body\.bp-page\),\s*\.dark-mode body\.bp-page,\s*\.dark-mode body\.bp-page \.wp-site-blocks \{\s*background-color: #0f172a;/');
        expect($code)->toMatch('/\.wp-site-blocks > footer,[^}]*\{\s*margin-block-end: 0;/');
        // From 1700 px the quote band starts a step and a half lower, so the quote sits below a 1080 px first screen even with five problem rows (governance dropped, 2026-11-13).
        expect($code)->toMatch('/@media \(min-width: 1700px\) \{\s*\.hero__grid \{\s*--ans-h: 200px;\s*\}\s*\.hero__band \{\s*padding-top: calc\(var\(--sp-8\) \+ var\(--sp-2\)\);/');
    });

    it('rests dark mode on deep navy and uses the second field only for the alternate bands', function () {
        $code = (string) preg_replace('#/\*.*?\*/#s', '', $this->css);

        expect($code)->toMatch('/\.dark-mode \.bp \{[^}]*--bp-bg: #0f172a;/s');
        expect($code)->toMatch('/\.bp-alt \{\s*background: var\(--bp-alt\);/');
    });

    it('redefines the tokens in dark mode and keeps focus rings', function () {
        expect($this->css)->toContain('.dark-mode .bp {');
        expect($this->css)->toContain(':focus-visible');
    });

    it('has a reduced-motion fallback for the entrance and the drift', function () {
        expect($this->css)->toContain('@media (prefers-reduced-motion: reduce)');
        expect($this->css)->toMatch('/\[data-reveal\]\s*\{\s*opacity: 1;\s*transform: none;/');
    });

    it('moves only transform and opacity', function () {
        preg_match_all('/transition:\s*([^;]+);/', $this->css, $matches);

        foreach ($matches[1] as $transition) {
            expect($transition)->not->toMatch('/\b(width|height|margin|padding|top|left)\b/');
        }
    });
});

describe('booking page script', function () {
    it('needs no library and leaves content visible without script', function () {
        expect($this->js)->not->toContain('jQuery');
        expect($this->js)->toContain("classList.add( 'bp-js' )");
        expect($this->js)->toContain('prefers-reduced-motion');
    });

    it('fills the topic field of the form when a level is chosen', function () {
        expect($this->js)->toContain('.bp-topic input');
        expect($this->js)->toContain('data-topic');
    });
});

describe('booking page template and pattern', function () {
    it('has a template for the page slug that uses the pattern and the site header', function () {
        $template = (string) file_get_contents($this->root . '/templates/page-public-speaking.html');

        expect($template)->toContain('"slug":"header"');
        expect($template)->toContain('crispytheme/booking-page');
    });

    it('registers a pattern that holds the dynamic block', function () {
        $pattern = (string) file_get_contents($this->root . '/block-patterns/booking-page.php');

        expect($pattern)->toContain("'crispytheme/booking-page'");
        expect($pattern)->toContain('BookingPage::content()');
    });

    it('ships a manifest for every photograph slot, with real files', function () {
        $dir      = $this->root . '/assets/images/booking';
        $manifest = json_decode((string) file_get_contents($dir . '/manifest.json'), true);

        expect($manifest)->not->toHaveKey('letter');
        expect($manifest)->toHaveKeys(['hero-43', 'reach', 'close', 'level', 'stages', 'fees', 'steps']);
        foreach ($manifest as $meta) {
            expect(file_exists($dir . '/' . $meta['file']))->toBeTrue();
            [$width, $height] = getimagesize($dir . '/' . $meta['file']);
            expect([$width, $height])->toBe([$meta['width'], $meta['height']]);
        }
    });

    it('shows no photograph with embedded metadata', function () {
        foreach (glob($this->root . '/assets/images/booking/*.jpg') as $file) {
            expect(function_exists('exif_read_data') ? (@exif_read_data($file) ?: []) : [])->not->toHaveKey('GPSLatitude');
        }
    });
});

describe('booking page assets', function () {
    it('enqueues its own stylesheet and a deferred script', function () {
        $styles  = [];
        $scripts = [];
        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle, string $src = '', array $deps = []) use (&$styles): void {
                $styles[$handle] = [$src, $deps];
            }
        );
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle, string $src = '', array $deps = [], $ver = false, $args = []) use (&$scripts): void {
                $scripts[$handle] = [$src, $args];
            }
        );

        $method = new ReflectionMethod(Assets::class, 'enqueue_booking_assets');
        $method->invoke(new Assets());

        expect($styles['crispy-theme-booking'][0])->toEndWith('/assets/css/booking-page.css');
        expect($styles['crispy-theme-booking'][1])->toContain('crispy-theme-layout');
        expect($scripts['crispy-theme-booking'][0])->toEndWith('/assets/js/booking-page.js');
        expect($scripts['crispy-theme-booking'][1])->toBe(['in_footer' => true, 'strategy' => 'defer']);
    });
});

describe('booking page options screen', function () {
    it('registers one setting for every fact', function () {
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(null);
        Functions\when('__')->returnArg(1);
        $registered = [];
        Functions\when('register_setting')->alias(
            static function (string $group, string $option, array $args = []) use (&$registered): void {
                $registered[$option] = $args;
            }
        );

        (new CrispyTheme\Admin\OptionsPage())->register_settings();

        foreach (BookingFacts::definitions() as $key => $definition) {
            $name = BookingFacts::option_name($key);
            expect($registered)->toHaveKey($name);
            expect($registered[$name]['default'])->toBe($definition['default']);
        }
        expect($registered[BookingFacts::option_name('fee_keynote_from')]['sanitize_callback'])->toBe([CrispyTheme\Admin\OptionsPage::class, 'sanitize_number']);
        expect($registered[BookingFacts::option_name('reel_url')]['sanitize_callback'])->toBe('esc_url_raw');
    });
});
