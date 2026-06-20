# PHP Compatibility Notes

**Supported PHP:** 8.1 – 8.3 (theme floor is `>=8.1`; `composer.json` and `style.css` declare `Requires PHP: 8.1`).

## Known third-party deprecation on PHP 8.4+

When CrispyTheme runs on **PHP 8.4 or 8.5**, the vendored Markdown library
`erusev/parsedown-extra` emits `E_DEPRECATED` notices such as:

```
ParsedownExtra::blockSetextHeader(): Implicitly marking parameter $Block as
nullable is deprecated, the explicit nullable type must be used instead
```

Facts:

- This originates in the **third-party `erusev/parsedown-extra`** dependency, **not** CrispyTheme's own code.
- It is a **deprecation notice**, not a warning, error, or fatal.
- It fires **only on PHP 8.4+** (the implicit-nullable deprecation was introduced in PHP 8.4). On the theme's supported range (8.1–8.3) the log is silent.
- It only triggers when Markdown content is rendered (the `the_content` path via `MarkdownRenderer`).

### Verification

A full local WordPress install (WP 7.0, theme activated) was rendered across the
homepage, single posts, pages, archives, the RSS feed, and a 404. Every page
returned the correct HTTP status and **zero CrispyTheme-originated entries** appeared
in `wp-content/debug.log` — the parsedown-extra deprecation above was the only log
content, and only because the test box runs PHP 8.5.

### If you want a literally empty log on PHP 8.4+

`erusev/parsedown` / `erusev/parsedown-extra` are effectively unmaintained, so there
is no clean upstream fix. Options, in order of preference, if you must silence it:

1. Run the site on a supported PHP version (8.1–8.3) — recommended for production.
2. Set `error_reporting` to exclude `E_DEPRECATED` at the platform/`php.ini` level
   (host-level, not theme code).
3. Replace the Markdown engine with a maintained parser (a larger change to the
   `Parser/` layer).

The theme intentionally does **not** add code to suppress third-party deprecations.
