# CrispyTheme v2

A high-performance, **Markdown-first WordPress block theme** with a **booking-first conversion homepage**, Schema.org structured data, IBM Plex typography, and dark mode.

- **Version:** 2.1.0 · **Requires:** WordPress 6.6+ · PHP 8.1–8.3 · **License:** MIT
- Content is authored as raw Markdown in `post_meta` (`_markdown_content`), rendered at runtime via Parsedown and cached with WordPress Transients — no page-builder bloat, version-control friendly.

> **v2 installs alongside v1.** It uses a distinct theme name (`CrispyTheme v2`) and is deployed to its own directory (`crispytheme-v2/`), so the original `CrispyTheme` (v1) stays installed and you can switch back in one click. See [Deployment & rollback](#deployment--rollback).

---

## What's new in v2.0

v2 converts the homepage from a content-discovery layout into a **booking-first conversion surface** (for event planners / enterprise buyers), per the TrustInsights conversion strategy in [`input/`](input/).

**Homepage redesign**
- **Hero:** 60/40 split — outcome headline + transformation subhead + dual CTA (`Book Christopher` solid · `Watch Demo Reel` ghost). Booking owns the fold; no speaking/newsletter split.
- **New scroll order:** hero → monochrome client-logo marquee → newsletter (pulled high) → metrics badge → booking block + a subordinate 3-ICP routing row (replaces the old four-card grid) → Vimeo demo reel (dark navy) → PostEventScore + planner testimonial → closing booking band → expanded footer.
- **Navigation:** `Home`/`Blog` removed, a persistent cyan **Book Christopher** button added; dark-mode toggle kept.
- **Design system:** cyan accent (`#06b6d4` light / `#22d3ee` dark) on a dark-navy `#0f172a` surface; **IBM Plex Serif → Sans headings (no serif)**; 18px body. CTAs are cyan-fill + navy-text and pass WCAG AA in both light and dark mode.

**New infrastructure**
- **Schema.org / JSON-LD** (`src/SEO/`): Person, Organization, VideoObject, and Speakable, emitted in `wp_head`. Defaults are **verified-only** (sourced from `docs/about.md`); empty fields are omitted — no fabricated data is ever published.
- **Subscriber-count helper** (`inc/template-tags.php`): a manual value wrapped in a transient, overridable later by a live email-platform API via the `crispytheme_subscriber_count` filter.
- **~25 new Theme Options** for booking CTAs, social proof, and schema configuration.
- Programmatic block patterns moved from `patterns/` → **`block-patterns/`** so they no longer trip WordPress core's reserved-directory pattern scanner.

**Quality**
- **100% line coverage** across `src/` (up from ~8%); **602 tests** pass.
- `composer quality` is green: PHPCS (WordPress standards), PHPStan level 6, Deptrac architecture, Pest.
- Verified end-to-end in a real local WordPress 7.0 install (every page type renders, schema emits, no theme-originated errors in the debug log).

A full design rationale lives in [`CLAUDE.md`](CLAUDE.md) (First Principles) and the strategy docs in [`input/`](input/).

---

## Requirements

| | |
|---|---|
| **WordPress** | 6.6 or newer |
| **PHP** | 8.1–8.3 (8.4+ works but the third-party Markdown library emits deprecations — see [`docs/php-compatibility.md`](docs/php-compatibility.md)) |
| **Production PHP dependencies** | `erusev/parsedown`, `erusev/parsedown-extra`, `league/html-to-markdown` |

---

## Deployment & rollback

v2 is designed to be installed **next to** v1 so you can roll back instantly by re-activating the old theme. Both themes can be installed at the same time; they share the same `crispytheme_*` options, so **no content or settings are lost when you switch between them.**

### 1. Build the production package

From the theme repo (this directory), build a lean zip with **production dependencies only**:

```bash
# Install production dependencies only (no phpstan/pest/rector/deptrac/phpcs)
composer install --no-dev --optimize-autoloader

# Build front-end assets
npm ci && npm run build

# Package the theme into a v2-named directory so it installs alongside v1.
# (WordPress identifies a theme by its DIRECTORY name — this is what enables
#  parallel install + rollback.) output/ is gitignored — never /tmp.
rm -rf output/crispytheme-v2 && mkdir -p output/crispytheme-v2
rsync -a --exclude-from=- ./ output/crispytheme-v2/ <<'EXCLUDES'
.git/
node_modules/
tests/
input/
references/
graphify-out/
.github/
output/
temp/
*.md
phpstan.neon
deptrac.yaml
patchwork.json
phpcs.xml
.phpcs.xml.dist
rector.php
webpack.config.js
opencode.json
package-lock.json
composer.lock
crispy-theme.zip
bin/
includes/
.DS_Store
EXCLUDES
( cd output && zip -rq crispytheme-v2.zip crispytheme-v2 )

# Restore dev dependencies for local development
composer install
```

You now have `output/crispytheme-v2.zip` with `vendor/` ~500 KB and the zip under ~1 MB.

> **Packaging discipline:** never ship dev dependencies. The production runtime needs only the three Composer packages above plus the built `build/` assets.

### 2. Install v2 (keeping v1 in place)

**Via the WordPress admin (recommended):**
1. **Appearance → Themes → Add New → Upload Theme**.
2. Upload `crispytheme-v2.zip`. It installs as **“CrispyTheme v2”** in `wp-content/themes/crispytheme-v2/`, leaving your existing **“CrispyTheme”** (v1) untouched.
3. **Do not activate yet** if you want to preview first — use **Live Preview**, or activate on a staging site.

**Via WP-CLI:**
```bash
wp theme install output/crispytheme-v2.zip        # installs alongside v1
wp theme list                                     # you should see both crispytheme and crispytheme-v2
wp theme activate crispytheme-v2                   # go live with v2
```

### 3. Configure v2

After activating, open **Appearance → Theme Options** and fill in the v2 fields (booking URL, hero copy, metrics, schema, social proof). See [Configuration](#configuration). Because options are shared, anything v1 already set is preserved.

### 4. Roll back to v1 (instant, zero downtime)

If v2 misbehaves:

- **Admin:** **Appearance → Themes → activate “CrispyTheme”** (v1). Done.
- **WP-CLI:** `wp theme activate crispytheme`

No re-deploy, no data loss — switching the active theme is instantaneous. Once you're confident in v2, you may delete v1 (`wp theme delete crispytheme`), but keeping it costs nothing.

### Rebuilding v1 from source (if you don't already have it installed)

The pre-v2 code is at commit `e7d486f`. To produce a v1 package:

```bash
git worktree add output/crispytheme-v1 e7d486f
cd output/crispytheme-v1 && composer install --no-dev && npm ci && npm run build
# zip the directory as "crispytheme" (v1's Theme Name is "CrispyTheme")
```

> Tip: tag the release commits so rollback is trivial — `git tag v1.1.0 e7d486f` and `git tag v2.0.0 HEAD`.

---

## Configuration

All runtime content is editable under **Appearance → Theme Options** (no code edits required). Key v2 sections:

| Section | What it controls |
|---|---|
| **Homepage Settings** | Hero image, eyebrow, headline, subheadline; the optional events, countries, and satisfaction stats (all blank by default until a sourced value exists); demo-reel video (Vimeo preferred), heading, description; up to 6 client logos |
| **Booking & Conversion CTAs** | `crispytheme_booking_url` (where every “Book Christopher” CTA points — set to `/public-speaking/#check-my-dates` for the booking page; defaults to `/contact-me/`); hero CTA labels; the newsletter subscriber count shown in the metrics badge |
| **Social Proof** | PostEventScore value/count/source and the planner testimonial (quote, name, title, organization, event + year, optional video). **Blank = hidden** — these blocks never render placeholder or unverified data |
| **SEO / Schema (JSON-LD)** | Person (name, headshot, job title, description, `sameAs` URLs), Organization (name, logo, booking email), and the demo-reel VideoObject (URL, name, description, thumbnail, upload date, ISO-8601 duration). Blank fields are omitted from output |
| **Layout / Markdown / RSS / Archive** | Sidebar & max content width, Parsedown flavor, RSS full vs. excerpt, archive display, Markdown shortcode replacements |

The booking form itself (a `/book-christopher` page with a pre-qualified form) is **out of theme scope** — build it with a forms plugin or custom code, then point `crispytheme_booking_url` at it.

---

## Homepage structure

The front page (`templates/front-page.html`) assembles these block patterns (all in `block-patterns/`):

1. `crispytheme/homepage-hero` — split hero + dual CTA
2. `crispytheme/homepage-clients` — monochrome logo marquee (reduced-motion-safe)
3. `crispytheme/newsletter-hero` — newsletter opt-in (pulled high)
4. `crispytheme/metrics-badge` — subscriber reach + IBM Champion / 8× Author
5. `crispytheme/homepage-booking` — dominant booking CTA + subordinate 3-ICP row
6. `crispytheme/homepage-video-showcase` — Vimeo/YouTube demo reel (renders when a URL is set)
7. `crispytheme/posteventscore` + `crispytheme/homepage-testimonial` — social proof (render when configured)
8. `crispytheme/booking-cta` — closing booking band

---

## Architecture

PSR-4 (`CrispyTheme\` → `src/`), strict types throughout, enforced by Deptrac:

```
Theme (orchestration) → Admin, CLI, Content, DarkMode, Parser, Cache, SEO
Admin → Content, Parser
CLI   → Content, Parser, Cache
Content → Parser, Cache
DarkMode, Parser, Cache, SEO → (leaf layers, no internal dependencies)
```

- **Theme/** — setup, asset enqueueing, `wp_head` hooks
- **Content/** — `MarkdownRenderer`, `ExcerptGenerator`, `RSSFilter`, `MarkdownDropdown` (the “Copy/View as Markdown for LLMs” feature), `HtmlToMarkdownConverter`
- **SEO/** — Schema.org JSON-LD builders + orchestrator *(new in v2)*
- **Admin/** — Editor (disables block editor), MetaBox, Preview, OptionsPage
- **Parser/**, **Cache/**, **DarkMode/**, **Code/**, **Newsletter/**, **Patterns/**, **CLI/**
- **inc/template-tags.php** — global template helpers *(new in v2)*

---

## Development

```bash
composer install        # PHP deps (incl. dev tooling)
npm ci                  # JS deps

composer quality        # lint + analyze + architecture + test (the full gate)
composer lint           # PHPCS (WordPress coding standards)
composer lint:fix       # auto-fix style
composer analyze        # PHPStan level 6
composer architecture   # Deptrac layer enforcement
composer test           # Pest (unit + integration + architecture)
composer test:unit      # unit only
vendor/bin/pest --coverage   # coverage report (100% across src/)

npm run build           # production assets
npm run start           # asset watch mode
```

Tests use **Pest** with **Brain Monkey** for WordPress function mocking, mirroring `src/` under `tests/Unit/` and `tests/Integration/`. A couple of genuinely-untestable lines (a terminal `exit` in the raw-Markdown endpoint, defensive directory guards) are excluded with documented `@codeCoverageIgnore` markers; everything else is actually executed.

---

## Notes

- **PHP 8.4+:** the vendored `erusev/parsedown-extra` emits deprecation notices on PHP 8.4+ (only when rendering Markdown). It's a third-party library, not theme code, and is silent on the supported 8.1–8.3 range. Details in [`docs/php-compatibility.md`](docs/php-compatibility.md).
- **Block patterns** live in `block-patterns/` (not the WordPress-reserved `patterns/` directory) because they self-register programmatically via `register_block_pattern()` rather than via `Title:`/`Slug:` file headers.
- **Generated artifacts** (`graphify-out/`) and tool state (`.opencode/`) are git-ignored.

---

## License

MIT © Christopher S. Penn
