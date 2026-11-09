<?php
/**
 * Guard against claims that Christopher Penn removed or replaced (2026-10-25).
 *
 * The theme source must not print "500+ events", the unsourced 98% and 25 countries stats, the old
 * subscriber count 294,000+, the "8× Author" credential, the old tagline, or a /contact/ default.
 * The approved public form of the count is "About 292K subscribers (October 2026)".
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

/**
 * List the source files that print text on the site.
 *
 * @return array<int, string>
 */
function crispyClaimSources(string $root): array
{
    $files = [];
    foreach (['block-patterns', 'parts', 'templates', 'src', 'inc'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (preg_match('/\.(php|html)$/', $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
    }
    $files[] = $root . '/functions.php';

    return $files;
}

describe('removed claims', function () {
    it('keeps the old counts, stats, and credentials out of the theme source', function () {
        $root = dirname(__DIR__, 3);

        foreach (crispyClaimSources($root) as $file) {
            $code = (string) file_get_contents($file);
            foreach (['294,000', '500+ events', '8× Author', '8× published author', 'Marketing AI Keynote Speaker', "'98%'", "'25'"] as $claim) {
                expect(str_contains($code, $claim))->toBeFalse(basename($file) . ' holds ' . $claim);
            }
        }
    });

    it('defaults every unsourced homepage stat to blank, so the stat does not show', function () {
        $root  = dirname(__DIR__, 3);
        $stats = (string) file_get_contents($root . '/block-patterns/speaking-stats.php');

        expect($stats)->toContain("get_option( 'crispytheme_stat_countries', '' )");
        expect($stats)->toContain("get_option( 'crispytheme_stat_satisfaction', '' )");
        expect($stats)->toContain("'' === trim( (string) \$value )");
    });

    it('shows the subscriber count with its month in every pattern that states it', function () {
        $root = dirname(__DIR__, 3);

        foreach (['newsletter-hero', 'newsletter-inline', 'audience-routing', 'metrics-badge'] as $pattern) {
            expect((string) file_get_contents($root . '/block-patterns/' . $pattern . '.php'))->toContain('crispytheme_get_subscriber_as_of');
        }
        expect((string) file_get_contents($root . '/parts/footer.html'))->toContain('about 292K subscribers (October 2026)');
    });

    it('points the footer Contact link and every booking fallback to the live contact page', function () {
        $root = dirname(__DIR__, 3);

        expect((string) file_get_contents($root . '/parts/footer.html'))->toContain('"url":"/contact-me/"');
        foreach (['booking-cta', 'homepage-booking', 'nav-booking-button', 'homepage-hero'] as $pattern) {
            expect((string) file_get_contents($root . '/block-patterns/' . $pattern . '.php'))->not->toContain("'/contact/'");
        }
    });

    it('states the credentials from the facts the booking page uses', function () {
        $badge = (string) file_get_contents(dirname(__DIR__, 3) . '/block-patterns/metrics-badge.php');

        expect($badge)->toContain("BookingFacts::get( 'ibm_champion_times' )");
        expect($badge)->toContain("BookingFacts::int( 'book_titles' )");
        expect($badge)->toContain("BookingFacts::int( 'book_editions' )");
    });
});

describe('booking route', function () {
    it('sends no Book link to the old Trust Insights speaker form', function () {
        $root = dirname(__DIR__, 3);

        foreach (crispyClaimSources($root) as $file) {
            expect(str_contains((string) file_get_contents($file), 'marketing-keynote-speakers'))->toBeFalse(basename($file));
        }
    });
});
