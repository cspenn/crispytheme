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

## Claude Skills

### /qa - Quality Assurance

Runs all code quality checks and reports results:

- **Lint**: PHPCS WordPress coding standards
- **Analyze**: PHPStan level 6 static analysis
- **Architecture**: Deptrac layer enforcement
- **Test**: Pest unit and integration tests

All checks run even if earlier ones fail, providing a complete report with pass/fail summary.

Usage: `/qa`
