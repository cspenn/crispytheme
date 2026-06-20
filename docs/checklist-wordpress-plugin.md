# WordPress Plugin Best Practices Checklist

## Security

- [ ] All `$_GET/$_POST/$_REQUEST/$_COOKIE/$_SERVER` sanitized inline, immediately
- [ ] `$wpdb->prepare()` all SQL—no exceptions
- [ ] `esc_html()` / `esc_attr()` / `esc_url()` / `esc_js()` at every output point
- [ ] No escaping at storage time
- [ ] No `esc_html(esc_html($x))` double-escape pattern
- [ ] `wp_create_nonce()` + `wp_verify_nonce()` on every state-changing request
- [ ] Nonce action names unique per operation
- [ ] `current_user_can()` for all authorization—no role-name checks
- [ ] `current_user_can('edit_post', $post_id)` for object-level authorization
- [ ] `is_admin()` NOT used as security control
- [ ] Every `register_rest_route()` has `permission_callback`
- [ ] `permission_callback` returns `WP_Error` 403 for unauthorized
- [ ] `wp_kses_post()` or `wp_kses()` for HTML in user content

## Architecture

- [ ] Single entry point with correct plugin header
- [ ] No business logic in entry point
- [ ] `if (!defined('ABSPATH')) exit;` in every PHP file
- [ ] PSR-4 autoloading via Composer
- [ ] `composer dump-autoload --optimize --classmap-authoritative` in deploy
- [ ] Service Container (not Singleton) for main plugin class
- [ ] Repository pattern for all DB access
- [ ] Dependencies injected via constructor/setter—no global singletons
- [ ] All names prefixed: functions, classes, options, transients, tables, hooks
- [ ] `$wpdb->prefix` used—not hardcoded `wp_`

## Performance

- [ ] `query_posts()` never used
- [ ] `WP_Query` with `'fields' => 'ids'` when full objects unnecessary
- [ ] `'no_found_rows' => true` when pagination unnecessary
- [ ] `'update_post_meta_cache' => false` / `'update_post_term_cache' => false` when unused
- [ ] `meta_query` in primary query—no `get_post_meta()` in loops
- [ ] `posts_per_page` has ceiling (not `-1` on archives)
- [ ] `post__not_in` array chunked/batched if >100 items
- [ ] Taxonomy queries preferred over `meta_query` at scale
- [ ] Transients for persistent cache with miss-regeneration fallback
- [ ] `wp_cache_*` for request-scoped in-memory cache
- [ ] Options with `autoload='yes'` kept minimal
- [ ] Conditional asset enqueueing—no full plugin init on every request
- [ ] `is_admin()` / `wp_is_json_request()` / `wp_doing_cron()` / `WP_CLI` guards
- [ ] Service Container lazy-loading implemented

## Asset Management

- [ ] `wp_enqueue_script()` / `wp_enqueue_style()` —no hardcoded tags
- [ ] Version via `filemtime()` for cache busting
- [ ] Dependencies declared in `$deps` array
- [ ] `wp_localize_script()` for static JS data
- [ ] `wp_add_inline_script()` for dynamic JS data
- [ ] Conditional loading on page context

## REST API

- [ ] Versioned namespace `/my-plugin/v1/`
- [ ] `sanitize_callback` on all args
- [ ] HTTP status codes: 200/201/400/401/403/404/500
- [ ] `WP_Error` for error responses

## Lifecycle

- [ ] Activation: tables (dbDelta), defaults, caps, cron, rewrite flush
- [ ] Deactivation: cron unschedule, rewrite flush—no data deletion
- [ ] `uninstall.php` deletes ALL: options, transients, postmeta, tables, uploads
- [ ] dbDelta two-space-after-`PRIMARY KEY` spacing exact

## Hooks

- [ ] Hooks registered in dedicated `register_*()` methods
- [ ] Plugin-prefixed hook names
- [ ] Priority explicit when order matters
- [ ] Object callbacks `[$this, 'method']` in class context

## Managed Hosting / WP Engine

- [ ] No `.htaccess` for functionality
- [ ] No `xmlrpc_*` functions
- [ ] File writes: plugin dir or `wp-content/uploads/` only
- [ ] Cron: 60s minimum OR Action Scheduler for sub-minute
- [ ] `DISABLE_WP_CRON` + system cron in production

## Multisite

- [ ] `is_multisite()` checks for `get_site_option()` / `update_site_option()`
- [ ] Network-activated check where required

## i18n

- [ ] `load_plugin_textdomain()` in `plugins_loaded` hook
- [ ] All UI strings in `__()` / `_e()` / `esc_html__()` / `esc_attr__()`
- [ ] No string concatenation inside translation functions
- [ ] `wp_localize_script()` for JS translation strings
- [ ] Text Domain matches plugin slug in header

## Error Handling

- [ ] Custom exception classes for plugin-specific errors
- [ ] All external ops wrapped in try-catch
- [ ] `error_log()` for debug output—no `var_dump()` / `print_r()`
- [ ] User-facing errors: generic messages, no stack traces
- [ ] REST errors include machine-readable error codes
- [ ] `WP_DEBUG` guard on debug logging

## Code Quality

- [ ] PHPCS zero errors (WordPress-Core + WordPress-Extra)
- [ ] PHPStan level 5+ with WordPress stubs
- [ ] `declare(strict_types=1);` in every PHP file
- [ ] PHPUnit coverage for service classes
- [ ] Integration tests for hooks, shortcodes, REST endpoints
- [ ] No deprecated functions (`query_posts`, `create_function`, etc.)

## WordPress.org Compliance

- [ ] readme.txt: all required sections present
- [ ] Plugin header: all 14 required fields
- [ ] License: GPLv2+ only
- [ ] No non-free dependencies
- [ ] `Stable tag:` matches version
- [ ] `Requires PHP:` matches `phpstan.neon` level
