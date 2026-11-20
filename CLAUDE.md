# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CrispyTheme is a Markdown-first WordPress Block Theme that bypasses Gutenberg's block storage. Content is stored as raw Markdown in `post_meta` (`_markdown_content`) and rendered at runtime via Parsedown, with results cached using WordPress Transients.

## Development Commands

```bash
# Install dependencies
composer install
npm ci

# Quality checks (run all)
composer quality    # Runs lint, analyze, architecture, test

# Individual checks
composer lint       # PHPCS - WordPress coding standards
composer lint:fix   # Auto-fix PHPCS issues
composer analyze    # PHPStan level 6
composer architecture  # Deptrac layer enforcement
composer test       # Pest tests
composer test:unit  # Unit tests only
composer test:integration  # Integration tests only

# Code modernization
composer rector     # Dry-run PHP 8.1 upgrades
composer rector:fix # Apply rector changes

# Frontend assets
npm run build       # Build production assets
npm run start       # Development watch mode
```

## Architecture

### Layer Structure (Enforced by Deptrac)

```
Theme (orchestration) → Admin, CLI, Content, DarkMode, Parser, Cache, SEO
Admin → Content, Parser
CLI → Content, Parser
Content → Parser, Cache
DarkMode, Parser, Cache, SEO → (no dependencies - leaf layers)
```

### Source Code Organization (`src/`)

- **Theme/**: Setup and Assets initialization, hooks into `after_setup_theme`
- **Content/**: MarkdownRenderer (the_content filter), ExcerptGenerator, RSSFilter
- **Parser/**: ParserInterface + ParserFactory for Parsedown abstraction
- **Cache/**: TransientCache wrapper for WordPress transients
- **Admin/**: Editor (disables block editor), MetaBox (markdown textarea), Preview, OptionsPage
- **CLI/**: WP-CLI import command (`wp crispy`)
- **DarkMode/**: Toggle functionality
- **SEO/**: Schema.org JSON-LD injection (Person, Organization, VideoObject, Speakable) via `wp_head`

### Entry Point

`functions.php` bootstraps everything via `crispy_theme_init()` which instantiates and calls `init()` on each component.

## Key Conventions

- **PHP 8.1+ required**, WordPress 6.6+
- **PSR-4 autoloading**: `CrispyTheme\` namespace maps to `src/`
- **Text domains**: `crispy-theme`
- **Prefixes**: `crispy`, `crispytheme` for hooks/options
- **Strict types**: All PHP files use `declare(strict_types=1)`

## Recommended: CrispySEO Plugin

For SEO functionality (meta tags, schema, sitemaps, redirects, image optimization), install the standalone **CrispySEO** plugin separately. The theme will display a soft recommendation in the admin if the plugin is not detected.

The plugin is available at: `crispy-seo/crispy-seo.php`

## Testing

Tests use Pest PHP with Brain Monkey for WordPress function mocking. Test files mirror `src/` structure under `tests/Unit/` and `tests/Integration/`. Run single test file: `vendor/bin/pest tests/Unit/ParserFactoryTest.php`

## First Principles

These guiding principles inform all design and development decisions for CrispyTheme.

### 1. Brand Alignment Over Feature Creep

Every theme decision must reinforce Christopher Penn's "Practitioner-Scholar" brand identity. Before adding any feature, ask: "Does this signal technical competence and anti-hype credibility?" Features that don't align with the brand should be rejected, even if technically interesting.

### 2. Consumption + Conversion Balance

The theme must support both **content consumption** (reading, discovering, engaging) AND **audience conversion** (newsletter signups, speaking inquiries, consulting leads). A blog-only focus is incomplete; conversion paths are equally critical.

**Homepage exception (resolved 2026-06-19 against the conversion report):** on the planner-facing homepage, **booking and newsletter are the primary actions and the blog is demoted to the footer.** Consumption paths remain primary on `/blog`, `/newsletter`, and the sidebar — the demotion is homepage-only.

### 3. Three ICP Focus

Every template and pattern should serve one of the three Ideal Customer Profiles:
- **ICP A (Event Planners)**: Need to quickly validate Penn as a "safe hands" speaker
- **ICP B (Enterprise Leaders)**: Need trust signals and proof of the method
- **ICP C (Technical Practitioners)**: Need code, dark mode, and "proud nerd" signals

On the homepage (resolved 2026-06-19), ICP routing is a **subordinate row beneath the dominant booking CTA** — not four equal cards — so booking stays the single dominant action while still routing all three ICPs.

### 4. Technical Authority Through Design

Visual choices must signal technical competence:
- **IBM Plex typography**: Designed for technical communication. Headings use **IBM Plex Sans** (geometric-humanist sans, **no serif**); accent is cyan `#06b6d4` with a dark-navy `#0f172a` surface (resolved 2026-06-19 against the conversion report — IBM Plex Sans satisfies the report's "no serif" intent while keeping the brand typeface)
- **Dark mode toggle**: Developer tribal signal
- **Markdown-first editing**: Version control friendly, no page builder bloat
- **Code showcase patterns**: Collapsible blocks, syntax highlighting, prompt/output displays

### 5. Restrained Design = Trust

Anti-hype aesthetic builds credibility. No flashy gradients, no excessive animation. The color palette is deliberately restrained: "I don't need to shout—the content speaks for itself."

### 6. Radical Transparency

Show the work. The theme should enable:
- Collapsible "Show the Code" sections
- Downloadable code snippets
- Open methodology disclosure
- Clear attribution and sourcing

### 7. Newsletter as Flywheel

The Almost Timely Newsletter (about 292K subscribers, October 2026; the count is a Theme Option) is the critical top-of-funnel. Newsletter signup must be:
- Prominent in sidebar
- Available as patterns (inline, hero)
- Integrated throughout conversion paths

### 8. Security First

All code must:
- Validate all inputs (type checking, bounds checking)
- Escape all outputs (context-appropriate escaping)
- Handle edge cases (empty values, missing URLs, failed operations)
- Avoid trusting user-provided data without sanitization

### 9. Production Packaging Discipline

Theme distribution must be lean and intentional:
- **Never include dev dependencies** in `crispy-theme.zip` (phpstan, pest, rector, deptrac, phpcs)
- **Always use** `composer install --no-dev` before packaging
- **Verify sizes**: vendor/ ~500KB, zip <1MB (not 100MB+)
- **Restore dev deps** after packaging with `composer install`

Production dependencies are: `erusev/parsedown`, `erusev/parsedown-extra`, `league/html-to-markdown`

## Claude Skills

### /qa - Quality Assurance

Runs all code quality checks and reports results:

- **Lint**: PHPCS WordPress coding standards
- **Analyze**: PHPStan level 6 static analysis
- **Architecture**: Deptrac layer enforcement
- **Test**: Pest unit and integration tests

All checks run even if earlier ones fail, providing a complete report with pass/fail summary.

Usage: `/qa`

## graphify

This project has a graphify knowledge graph at graphify-out/.

Rules:
- Before answering architecture or codebase questions, read graphify-out/GRAPH_REPORT.md for god nodes and community structure
- If graphify-out/wiki/index.md exists, navigate it instead of reading raw files
- After modifying code files in this session, run `graphify update .` to keep the graph current (AST-only, no API cost)
