<?php
/**
 * Regression guard for the mobile sidebar-crush bug (v2.0.3).
 *
 * Root cause: WordPress block-supports emits `body .is-layout-flex { display:
 * flex }` (specificity 0,1,1) for any group with a flex layout attribute —
 * which every sidebar-bearing template's `.site-main__inner` group declared.
 * A bare `.site-main__inner { display: grid }` (specificity 0,1,0) always
 * loses that fight, so the theme's two-column grid never applied, at any
 * viewport. Below 782px the only rule that *did* apply was
 * `.site-main__inner .sidebar-area { width: 100% }`, which in the resulting
 * nowrap flex row handed the sidebar the entire row and crushed the content
 * column to a sliver.
 *
 * The fix qualifies every rule that must outrank block-supports with
 * `.wp-block-group` (specificity 0,2,0) — including inside the mobile media
 * query, where the same trap exists in miniature: if only the desktop rule
 * were bumped, it would outrank an un-bumped mobile override and mobile would
 * never stack. This test asserts both qualifiers are present and the
 * conflicting `width` declarations that caused the crush are gone.
 *
 * These are static assertions against the CSS source, not a rendered-DOM
 * check — see tests/visual/sidebar-layout.html for the visual reproduction
 * and fix verification (a static, no-browser iframe harness built from a real
 * HAR capture of the live site plus WordPress core's actual generated CSS).
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

beforeEach(function () {
    $this->css = file_get_contents(dirname(__DIR__, 3) . '/assets/css/layout.css');
});

describe('assets/css/layout.css — sidebar grid specificity fix', function () {
    it('qualifies the desktop two-column rule with .wp-block-group', function () {
        // (0,2,0) — must outrank WordPress' `body .is-layout-flex { display: flex }` (0,1,1).
        expect($this->css)->toContain('.site-main__inner.wp-block-group {');
    });

    it('does not declare the two-column rule on the bare class alone', function () {
        // A bare `.site-main__inner {` (0,1,0) is exactly the regression: it loses to
        // block-supports and `display: grid` never applies, at any viewport.
        expect($this->css)->not->toContain(".site-main__inner {\n");
    });

    it('qualifies the mobile single-column override with the same specificity', function () {
        // Must match the desktop rule's (0,2,0). If this were left at (0,1,0), the
        // unconditional desktop rule would outrank it inside the media query and
        // mobile would never stack — the same bug in miniature.
        expect(substr_count($this->css, '.site-main__inner.wp-block-group {'))->toBe(2);
    });

    it('has grid-template-columns declared inside a @media (max-width: 782px) block', function () {
        expect($this->css)->toMatch(
            '/@media \(max-width:\s*782px\)\s*\{.*?\.site-main__inner\.wp-block-group\s*\{\s*grid-template-columns:\s*minmax\(0,\s*1fr\)/s'
        );
    });

    it('no longer sets an explicit width on any rule targeting .sidebar-area', function () {
        // The grid track owns sidebar width now. A second `width` declaration on
        // .sidebar-area is what produced the `width: 100%` mobile override that
        // crushed the content column in the first place. Scoped to rule bodies
        // whose selector mentions .sidebar-area (a bare `width:` line elsewhere in
        // the file — e.g. full-width headers — is unrelated and legitimate).
        preg_match_all('/([^{}]*\.sidebar-area[^{}]*)\{([^}]*)\}/', $this->css, $matches, PREG_SET_ORDER);

        expect($matches)->not->toBeEmpty();

        foreach ($matches as $match) {
            [, $selector, $body] = $match;
            $hasWidth = (bool) preg_match('/(?<![-\w])width\s*:/', $body);

            expect($hasWidth)->toBeFalse(
                "selector `{$selector}` sets an explicit width, reintroducing a second source of truth"
            );
        }
    });

    it('no longer reorders the sidebar with `order`', function () {
        expect($this->css)->not->toContain('order: 2');
    });

    it('zeroes margin-block on both grid items', function () {
        // Neutralizes both possible block-supports spacing models (flex-gap,
        // flow-margin) so the fix holds even if template markup renders flow
        // instead of flex — see tests/visual/fixtures/wp-generated-flow.css.
        expect($this->css)->toContain('margin-block: 0;');
    });
});

describe('assets/css/layout.css — admin-configurable width vars', function () {
    it('consumes --crispy-sidebar-width-base via var() with a fallback, never redeclares it', function () {
        expect($this->css)->toContain('--crispy-sidebar-width: var(--crispy-sidebar-width-base, 350px);');
    });

    it('consumes --crispy-max-width-base via var() with a fallback, never redeclares it', function () {
        expect($this->css)->toContain('--crispy-max-width: var(--crispy-max-width-base, 1800px);');
    });
});
