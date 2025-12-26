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
Theme (orchestration) → Admin, CLI, Content, DarkMode, Parser, Cache
Admin → Content, Parser
CLI → Content, Parser
Content → Parser, Cache
DarkMode, Parser, Cache → (no dependencies - leaf layers)
```

### Source Code Organization (`src/`)

- **Theme/**: Setup and Assets initialization, hooks into `after_setup_theme`
- **Content/**: MarkdownRenderer (the_content filter), ExcerptGenerator, RSSFilter
- **Parser/**: ParserInterface + ParserFactory for Parsedown abstraction
- **Cache/**: TransientCache wrapper for WordPress transients
- **Admin/**: Editor (disables block editor), MetaBox (markdown textarea), Preview, OptionsPage
- **CLI/**: WP-CLI import command (`wp crispy`)
- **DarkMode/**: Toggle functionality

### Entry Point

`functions.php` bootstraps everything via `crispy_theme_init()` which instantiates and calls `init()` on each component.

## Key Conventions

- **PHP 8.1+ required**, WordPress 6.6+
- **PSR-4 autoloading**: `CrispyTheme\` namespace maps to `src/`
- **Text domains**: `crispy-theme`, `crispy-seo`
- **Prefixes**: `crispy`, `crispytheme`, `crispy_seo` for hooks/options
- **Strict types**: All PHP files use `declare(strict_types=1)`

## Bundled SEO Plugin

`includes/crispy-seo/` contains a bundled SEO plugin loaded via `plugins_loaded` hook. It has its own `vendor/` directory.

## Testing

Tests use Pest PHP with Brain Monkey for WordPress function mocking. Test files mirror `src/` structure under `tests/Unit/` and `tests/Integration/`. Run single test file: `vendor/bin/pest tests/Unit/ParserFactoryTest.php`

## First Principles

These guiding principles inform all design and development decisions for CrispyTheme.

### 1. Brand Alignment Over Feature Creep

Every theme decision must reinforce Christopher Penn's "Practitioner-Scholar" brand identity. Before adding any feature, ask: "Does this signal technical competence and anti-hype credibility?" Features that don't align with the brand should be rejected, even if technically interesting.

### 2. Consumption + Conversion Balance

The theme must support both **content consumption** (reading, discovering, engaging) AND **audience conversion** (newsletter signups, speaking inquiries, consulting leads). A blog-only focus is incomplete; conversion paths are equally critical.

### 3. Three ICP Focus

Every template and pattern should serve one of the three Ideal Customer Profiles:
- **ICP A (Event Planners)**: Need to quickly validate Penn as a "safe hands" speaker
- **ICP B (Enterprise Leaders)**: Need trust signals and governance-focused methodology proof
- **ICP C (Technical Practitioners)**: Need code, dark mode, and "proud nerd" signals

### 4. Technical Authority Through Design

Visual choices must signal technical competence:
- **IBM Plex typography**: Designed for technical communication
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

The Almost Timely Newsletter (294k+ subscribers) is the critical top-of-funnel. Newsletter signup must be:
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
