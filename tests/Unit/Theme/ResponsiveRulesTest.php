<?php
/**
 * Regression guard for the design fixes of 2026-10-25 (design-taste-frontend, round 1).
 *
 * The inspector measured these failures on the local copy of the site:
 * - Phone overflow: the booking page rendered 524px wide, the about and contact pages 454px,
 *   at a 390px screen. Cause: fixed 500px YouTube iframes, and a long site title that
 *   could not shrink.
 * - Thin rules under headings, in the header, and in the footer.
 * - A shadow on the hero image.
 * - Tiny uppercase letter-spaced labels.
 * - "See Christopher in Action" at 1.06 to 1 in dark mode.
 * - A home headline of three lines at 1440px and five lines at 390px.
 * - The "AI Tools for This Page" button on the first screen of public pages.
 *
 * These are static assertions against the CSS and pattern source. The rendered result is
 * measured with src/theme_probe.mjs and inspect.mjs in the research project.
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

/**
 * Return the declaration block of the first rule with $selector in its selector list.
 *
 * Commas inside parentheses, as in :where(a, b), do not split the list. The match is exact,
 * so ".site-branding" does not match ".site-branding .wp-block-site-title".
 */
function crispyRuleBody(string $css, string $selector): ?string
{
    $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
    preg_match_all('/(?:^|\})\s*([^{}@]+)\{([^{}]*)\}/m', $css, $rules, PREG_SET_ORDER);

    foreach ($rules as $rule) {
        $selectors = preg_split('/,(?![^()]*\))/', (string) $rule[1]) ?: [];
        $selectors = array_map(static fn (string $item): string => trim((string) preg_replace('/\s+/', ' ', $item)), $selectors);

        if (in_array($selector, $selectors, true)) {
            return $rule[2];
        }
    }

    return null;
}

beforeEach(function () {
    $root              = dirname(__DIR__, 3);
    $this->layout      = file_get_contents($root . '/assets/css/layout.css');
    $this->header      = file_get_contents($root . '/assets/css/header.css');
    $this->markdown    = file_get_contents($root . '/assets/css/github-markdown.css');
    $this->markdownDk  = file_get_contents($root . '/assets/css/github-markdown-dark.css');
    $this->patterns    = file_get_contents($root . '/assets/css/patterns.css');
    $this->newsletter  = file_get_contents($root . '/assets/css/newsletter.css');
    $this->dropdown    = file_get_contents($root . '/assets/css/markdown-dropdown.css');
    $this->footerPart  = file_get_contents($root . '/parts/footer.html');
    $this->heroPattern = file_get_contents($root . '/block-patterns/homepage-hero.php');
    $this->reel        = file_get_contents($root . '/block-patterns/homepage-video-showcase.php');
    $this->themeJson   = json_decode(file_get_contents($root . '/theme.json'), true);
    $this->patternDir  = $root . '/block-patterns';
});

describe('phone overflow', function () {
    it('caps images, video, canvas and iframes at the width of the column', function () {
        expect($this->layout)->toContain(':where(img, video, canvas, iframe) {');
        expect(crispyRuleBody($this->layout, ':where(.markdown-body, .entry-content, .wp-block-post-content) :where(img, video, canvas, iframe)'))->toContain('max-width: 100%;');
    });

    it('gives YouTube and Vimeo iframes a 16 to 9 ratio instead of a fixed height', function () {
        $body = crispyRuleBody($this->layout, ':where(.markdown-body, .entry-content, .wp-block-post-content) :where(iframe[src*="youtube"], iframe[src*="youtu.be"], iframe[src*="vimeo"])');

        expect($body)->not->toBeNull();
        expect($body)->toContain('aspect-ratio: 16 / 9;');
        expect($body)->toContain('height: auto;');
        expect($this->layout)->toContain('iframe[src*="vimeo"]');
    });

    it('lets code and tables scroll inside their own box', function () {
        $body = crispyRuleBody($this->layout, ':where(.markdown-body, .entry-content, .wp-block-post-content) :where(pre, table)');

        expect($body)->not->toBeNull();
        expect($body)->toContain('max-width: 100%;');
        expect($body)->toContain('overflow-x: auto;');
    });

    it('uses :where() so the absolute-fill video rules keep winning', function () {
        // A selector with specificity above (0,1,1) would override .video-hero iframe height: 100%.
        expect($this->layout)->toContain(':where(.markdown-body, .entry-content, .wp-block-post-content)');
    });

    it('lets the site branding shrink so a long site title wraps', function () {
        $body = crispyRuleBody($this->header, '.site-branding');

        expect($body)->not->toBeNull();
        expect($body)->not->toContain('flex-shrink: 0');
        expect($body)->toContain('min-width: min(100%, 22rem);');
    });

    it('gives header, footer and page one side gutter', function () {
        $body = crispyRuleBody($this->layout, '.site-header.wp-block-group');

        expect($body)->not->toBeNull();
        expect($body)->toContain('padding-left: var(--wp--preset--spacing--50);');
        expect($body)->toContain('padding-right: var(--wp--preset--spacing--50);');
        expect($this->layout)->toContain('.site-footer.wp-block-group');
    });

    it('removes the extra hero gutter on a phone', function () {
        expect($this->patterns)->toMatch(
            '/@media \(max-width:\s*781px\)\s*\{[^@]*?\.site-main \.homepage-hero\.alignfull\s*\{\s*padding-left:\s*0;\s*padding-right:\s*0;/s'
        );
    });
});

describe('no thin rules and no resting shadows', function () {
    it('draws no rule under markdown h1 and h2', function () {
        foreach (['.markdown-body h1', '.markdown-body h2'] as $selector) {
            $body = crispyRuleBody($this->markdown, $selector);

            expect($body)->not->toBeNull();
            expect($body)->not->toContain('border-bottom');
        }
    });

    it('has no dark mode rule that recolors a heading rule', function () {
        expect($this->markdownDk)->not->toMatch('/\.markdown-body h[12][^{}]*\{[^}]*border-bottom/s');
    });

    it('has no heading rule in patterns.css', function () {
        expect($this->patterns)->not->toMatch('/\bh[1-6]\b[^{}]*\{[^}]*border-(top|bottom)\s*:/s');
    });

    it('separates the header from the page with a tone step, not a line', function () {
        $body = crispyRuleBody($this->header, '.site-header');

        expect($body)->not->toBeNull();
        expect($body)->not->toContain('border-bottom');
        expect($body)->toContain('background-color: var(--wp--preset--color--base-2);');
        expect($this->header)->not->toContain('.dark-mode .site-header {');
    });

    it('draws no border above the footer bottom row', function () {
        expect($this->footerPart)->not->toContain('border-top');
        expect($this->footerPart)->toContain('site-footer__bottom');
    });

    it('puts no shadow on the hero image', function () {
        $body = crispyRuleBody($this->patterns, '.homepage-hero__image img');

        expect($body)->not->toBeNull();
        expect($body)->not->toContain('box-shadow');
    });
});

describe('labels in sentence case', function () {
    it('has no uppercase letter-spaced label in the page stylesheets', function () {
        foreach (['patterns', 'newsletter', 'layout'] as $name) {
            expect($this->{$name})->not->toContain('text-transform: uppercase');
        }
    });

    it('has no uppercase label set inline in a pattern', function () {
        foreach (glob($this->patternDir . '/*.php') as $file) {
            expect(file_get_contents($file))->not->toContain('text-transform:uppercase');
            expect(file_get_contents($file))->not->toContain('"textTransform":"uppercase"');
        }
    });

    it('sets the hero eyebrow and the stat label at a readable size', function () {
        foreach (['.homepage-hero-eyebrow', '.stat-label'] as $selector) {
            $body = crispyRuleBody($this->patterns, $selector);

            expect($body)->not->toBeNull();
            expect($body)->toContain('font-size: 0.9375rem;');
        }
    });

    it('keeps a row gap over 72px so a wrapped label is not read as an eyebrow', function () {
        $body = crispyRuleBody($this->patterns, '.metrics-badge .metrics-badge__row');

        expect($body)->not->toBeNull();
        expect($body)->toContain('row-gap: 5rem;');
    });
});

describe('contrast in light and dark mode', function () {
    it('defines on-dark colors that no dark mode rule redefines', function () {
        $palette = array_column($this->themeJson['settings']['color']['palette'], 'color', 'slug');

        expect($palette)->toHaveKey('on-dark');
        expect($palette)->toHaveKey('on-dark-secondary');
        expect($this->markdownDk)->not->toContain('--wp--preset--color--on-dark');
    });

    it('sets the reel heading and description on the on-dark colors', function () {
        expect($this->reel)->toContain('"textColor":"on-dark"');
        expect($this->reel)->toContain('has-on-dark-color');
        expect($this->reel)->toContain('"textColor":"on-dark-secondary"');
        expect($this->reel)->toContain('has-on-dark-secondary-color');
        // The "background" token turns near-black in dark mode, which sat on the navy surface.
        expect($this->reel)->not->toContain('"textColor":"background"');
        expect($this->reel)->not->toContain('has-background-color');
    });

    it('passes 4.5 to 1 for both on-dark colors on the navy surface', function () {
        $palette = array_column($this->themeJson['settings']['color']['palette'], 'color', 'slug');
        $ratio   = static function (string $a, string $b): float {
            $luminance = static function (string $hex): float {
                $channels = array_map(
                    static function (string $pair): float {
                        $v = hexdec($pair) / 255;
                        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
                    },
                    str_split(ltrim($hex, '#'), 2)
                );
                return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
            };
            $high = max($luminance($a), $luminance($b));
            $low  = min($luminance($a), $luminance($b));

            return ($high + 0.05) / ($low + 0.05);
        };

        expect($ratio($palette['on-dark'], $palette['surface-dark']))->toBeGreaterThan(4.5);
        expect($ratio($palette['on-dark-secondary'], $palette['surface-dark']))->toBeGreaterThan(4.5);
    });
});

describe('headline size', function () {
    it('sets the home headline with a fluid clamp and no fixed 64px floor', function () {
        expect($this->heroPattern)->toContain('clamp(1.75rem, 1.1rem + 2.4vw, 3.25rem)');
        expect($this->heroPattern)->not->toContain('clamp(3rem, 6vw, 4rem)');
    });

    it('tightens the home headline tracking and balances its lines', function () {
        $body = crispyRuleBody($this->patterns, '.homepage-hero__title');

        expect($body)->not->toBeNull();
        expect($body)->toContain('letter-spacing: -0.02em;');
        expect($body)->toContain('text-wrap: balance;');
    });

    it('keeps the desktop size of page titles and section headings as the clamp ceiling', function () {
        expect($this->layout)->toContain('var(--wp--preset--font-size--xxx-large)');
        expect($this->layout)->toContain('var(--wp--preset--font-size--xx-large)');
        expect($this->layout)->toContain('.site-main .wp-block-post-title');
        expect($this->layout)->toContain('.site-main h2.wp-block-heading');
    });
});

describe('AI tools button', function () {
    it('hides the button on public pages for visitors who are not logged in', function () {
        $body = crispyRuleBody($this->dropdown, 'body.page:not(.logged-in) .crispy-markdown-dropdown');

        expect($body)->not->toBeNull();
        expect($body)->toContain('display: none;');
    });

    it('does not hide the button on posts', function () {
        expect($this->dropdown)->not->toContain('body.single:not(.logged-in)');
        expect($this->dropdown)->not->toContain('body:not(.logged-in) .crispy-markdown-dropdown');
    });
});

it('leaves the hero label off by default and names the offer in the subhead', function () {
	$hero = file_get_contents( dirname( __DIR__, 3 ) . '/block-patterns/homepage-hero.php' );
	expect( $hero )->toContain( "get_option( 'crispytheme_hero_eyebrow', '' )" )
		->and( $hero )->toContain( "'' === \$hero_eyebrow" )
		->and( $hero )->toContain( 'AI builder and keynote speaker' )
		->and( $hero )->toContain( 'Five Levels of AI Enablement' )
		->and( $hero )->not->toContain( '294,000+' );
});

it('ships no unsourced events count and names the Fortune 500 clients Penn confirmed', function () {
	$root = dirname( __DIR__, 3 );
	foreach ( [ 'block-patterns/metrics-badge.php', 'block-patterns/speaking-stats.php', 'src/Admin/OptionsPage.php' ] as $file ) {
		expect( file_get_contents( $root . '/' . $file ) )->not->toContain( "'500+'" );
	}
	$booking = file_get_contents( $root . '/block-patterns/homepage-booking.php' );
	expect( $booking )->toContain( 'Fortune 500 companies including Pfizer, T-Mobile, and McDonald' )
		->and( $booking )->not->toContain( 'Fortune 500 organizations worldwide' );
});

