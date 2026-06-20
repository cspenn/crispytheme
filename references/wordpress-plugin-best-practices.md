# Enterprise WordPress Plugin Development Best Practices
## Master Report: Safe, Secure, Efficient, Small-Memory-Footprint, High-Performance Plugins

**Version**: 1.0
**Date**: 2026-04-03
**Sources**: 3 independent research reports (Perplexity, Qwen, Gemini) synthesized via conflict resolution

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Plugin Architecture and File Structure](#2-plugin-architecture-and-file-structure)
3. [Security](#3-security)
   - 3.1 [Input Sanitization](#31-input-sanitization)
   - 3.2 [Output Escaping](#32-output-escaping)
   - 3.3 [CSRF Protection](#33-csrf-protection)
   - 3.4 [Capability Checks and Access Control](#34-capability-checks-and-access-control)
   - 3.5 [REST API Authentication and Permissions](#35-rest-api-authentication-and-permissions)
4. [Performance Optimization](#4-performance-optimization)
   - 4.1 [Database Query Optimization](#41-database-query-optimization)
   - 4.1.1 [Custom Tables vs. Native Storage](#411-custom-tables-vs-native-storage)
   - 4.2 [Caching Strategies](#42-caching-strategies)
   - 4.3 [Memory Management and Lazy Loading](#43-memory-management-and-lazy-loading)
5. [Asset Management](#5-asset-management)
6. [Internationalization and Localization](#6-internationalization-and-localization)
7. [REST API Design](#7-rest-api-design)
8. [Plugin Lifecycle: Activation, Deactivation, Uninstall](#8-plugin-lifecycle-activation-deactivation-uninstall)
9. [Hook System Best Practices](#9-hook-system-best-practices)
10. [Managed Hosting and WP Engine Constraints](#10-managed-hosting-and-wp-engine-constraints)
11. [Compatibility: Multisite and Other Plugins](#11-compatibility-multisite-and-other-plugins)
12. [Code Quality: Standards and Testing](#12-code-quality-standards-and-testing)
13. [Design Patterns](#13-design-patterns)
14. [Error Handling and Logging](#14-error-handling-and-logging)
15. [Antipattern Catalog](#15-antipattern-catalog)
16. [WordPress.org Review Compliance](#16-wordpressorg-review-compliance)

---

## 1. Introduction

### Purpose

This master report synthesizes enterprise-grade WordPress plugin development best practices from three independent research sources. The guidance covers five core priorities:

1. **Safe** - Security-first design following OWASP-aligned practices
2. **Secure** - Comprehensive protection against SQL injection, XSS, CSRF
3. **Efficient** - Optimized database queries and resource usage
4. **Small-Memory-Footprint** - Lazy loading and contextual initialization
5. **Highly Performant** - Caching strategies and query optimization

### Intended Audience

Mid-to-senior PHP/WordPress developers building production-grade, commercially-distributed, or enterprise WordPress plugins. The content assumes familiarity with:

- PHP 8.1+ and PSR-4 autoloading
- WordPress hooks (`add_action`, `add_filter`)
- WordPress APIs (`$wpdb`, `WP_Query`)
- Basic security concepts (SQL injection, XSS, CSRF)

### How to Use This Document

Each section follows a consistent structure:
- **Guidance**: Prescriptive recommendations
- **Code Examples**: Concrete, copy-pasteable implementations
- **QA Criteria**: Pass/fail checklist for code review

---

## 2. Plugin Architecture and File Structure

### 2.1 Recommended Directory Layout

```
my-plugin/
├── my-plugin.php           # Single entry point (only file with plugin header)
├── composer.json           # PSR-4 autoloading configuration
├── uninstall.php           # Complete data cleanup (NOT deactivation hook)
├── includes/               # PSR-4 PHP source classes (or use 'src/')
│   ├── class-plugin-name.php
│   ├── class-loader.php
│   └── ...
├── admin/                  # Admin-only functionality
│   ├── class-admin.php
│   └── assets/            # Admin CSS/JS
├── public/                 # Frontend functionality
│   ├── class-public.php
│   └── assets/            # Frontend CSS/JS
├── assets/                 # Raw source assets (SCSS, TypeScript)
├── build/                   # Compiled/minified output
├── languages/               # Translation files (.pot, .po, .mo)
└── tests/                   # PHPUnit test suite
```

### 2.2 Entry Point Requirements

The main plugin file (`my-plugin.php`) must only:
- Define plugin header comment
- Define constants (`MY_PLUGIN_VERSION`, `MY_PLUGIN_PATH`, `MY_PLUGIN_URL`)
- Load Composer autoloader
- Bootstrap the plugin via a service container

**DO NOT** include business logic, hook registrations, or class definitions in the entry point.

### 2.3 Direct Access Protection

Add this at the top of any PHP file that could be accessed directly:

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
```

### 2.4 PSR-4 Autoloading

Configure Composer for PSR-4 autoloading:

```json
{
    "name": "vendor/my-awesome-plugin",
    "type": "wordpress-plugin",
    "autoload": {
        "psr-4": {
            "My_Plugin\\": "includes/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "My_Plugin\\Tests\\": "tests/"
        }
    }
}
```

Run `composer dump-autoload --optimize --classmap-authoritative` for production.

### 2.5 Service Container Bootstrap

Instead of Singleton pattern, use a Service Container:

```php
<?php
declare(strict_types=1);

namespace My_Plugin;

class Plugin_Service_Container {
    private static ?self $instance = null;
    private array $services = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_service(string $name, callable $factory): void {
        $this->services[$name] = $factory;
    }

    public function get_service(string $name) {
        return $this->services[$name]($this);
    }

    private function __construct() {
        $this->register_default_services();
    }

    private function register_default_services(): void {
        $this->register_service('admin', fn() => new Admin());
        $this->register_service('public', fn() => new Public());
        $this->register_service('repository', fn() => new Post_Repository());
    }
}

function my_plugin(): Plugin_Service_Container {
    return Plugin_Service_Container::get_instance();
}
```

### QA Criteria

- [ ] Single entry point file with plugin header
- [ ] No business logic in entry point file
- [ ] PSR-4 autoloading configured via Composer
- [ ] Direct access protection (`if (!defined('ABSPATH')) exit;`) in all PHP files
- [ ] Service Container used instead of Singleton for main plugin class
- [ ] `uninstall.php` exists for complete data cleanup

---

## 3. Security

Security is the highest priority. All WordPress security practices follow OWASP-aligned guidance.

### 3.1 Input Sanitization

Sanitize ALL data upon entry. Never trust any external input.

#### Sanitization Functions Table

| Function | Use Case |
|----------|----------|
| `sanitize_text_field()` | Basic text input (names, descriptions) |
| `sanitize_email()` | Email addresses |
| `absint()` | Non-negative integers (IDs, counts) |
| `intval()` | Integer values including negative |
| `sanitize_key()` | Array/object keys |
| `sanitize_title()` | URL-friendly slugs |
| `sanitize_file_name()` | Uploaded file names |
| `esc_url_raw()` | URLs for database/storage |
| `wp_kses_post()` | Limited HTML in user content (posts, comments) |
| `wp_kses()` | Custom HTML filtering with allowed protocols/elements |

#### Implementation Pattern

```php
<?php
// NEVER trust raw superglobal data
$name = sanitize_text_field($_POST['name']);
$email = sanitize_email($_POST['email']);
$user_id = absint($_GET['user_id']);
$status = sanitize_key($_POST['status']);

// For HTML content with limited tags
$content = wp_kses_post($_POST['content']);
```

#### Key Principle

Sanitization and escaping are TWO separate operations:
- **Sanitization**: Prepares data for storage/internal use
- **Escaping**: Prepares data for display (output time only)

### QA Criteria

- [ ] Every `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_SERVER` access is sanitized immediately
- [ ] Context-specific sanitization functions used (not generic `filter_var`)
- [ ] No raw user input concatenated into SQL queries
- [ ] HTML content uses `wp_kses_post()` or `wp_kses()` with explicit allowed tags

---

### 3.2 Output Escaping

**Escape Late Principle**: Escape at the exact moment of output, never at storage.

#### Escaping Functions Table

| Function | Context |
|----------|---------|
| `esc_html()` | Text within HTML tags |
| `esc_attr()` | HTML attribute values (title, alt, value, class) |
| `esc_url()` | URLs in href, src attributes |
| `esc_js()` | JavaScript strings |
| `esc_textarea()` | textarea content |
| `esc_html__()` | Translated text that is HTML |
| `esc_attr__()` | Translated attribute text |

#### Implementation Pattern

```php
<?php
// NEVER escape at storage time
// update_post_meta($post_id, 'subtitle', $subtitle); // No escaping here

// ALWAYS escape at output time
echo esc_html($subtitle);
echo esc_attr($title_attribute);
echo esc_url($link_href);
?>

<!-- Wrong: escaping at storage -->
<input value="<?php echo esc_attr($value); // WRONG if already escaped at storage ?>">

<!-- Correct: raw storage, escaped output -->
<input value="<?php echo esc_attr($value); // CORRECT ?>">
```

#### Double Escaping Prevention

```php
<?php
// If data is already sanitized for storage, do not re-escape
$title = sanitize_text_field($_POST['title']);
// At output:
echo esc_html($title); // Once is correct
echo esc_html(esc_html($title)); // DOUBLE ESCAPING produces &amp;amp;
```

### QA Criteria

- [ ] `esc_html()`, `esc_attr()`, `esc_url()` used at every output point
- [ ] No escaping performed at storage time
- [ ] No double-escaping (search for `esc_html(esc_html` pattern)
- [ ] Translation strings use `esc_html__()` not just `__()`

---

### 3.3 CSRF Protection

Protect all state-changing requests with WordPress nonces.

#### Nonce Implementation

```php
<?php
// CREATE: Generate nonce in forms
$nonce = wp_create_nonce('my_plugin_delete_item_' . $item_id);
?>
<form method="post">
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
    <input type="hidden" name="action" value="delete_item">
    <input type="hidden" name="item_id" value="<?php echo esc_attr($item_id); ?>">
    <button type="submit">Delete</button>
</form>

<?php
// VERIFY: Check nonce BEFORE any business logic
if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'my_plugin_delete_item_' . absint($_POST['item_id']))) {
    wp_die(esc_html__('Security check failed.', 'my-plugin'));
}

// NOW safe to proceed with business logic
$item_id = absint($_POST['item_id']);
// ... delete item
```

#### URL-Based Nonces

```php
<?php
$delete_url = wp_nonce_url(
    add_query_arg(['action' => 'delete', 'id' => $item_id], admin_url('admin.php')),
    'my_plugin_delete_' . $item_id
);
echo '<a href="' . esc_url($delete_url) . '">Delete</a>';
```

#### Key Principles

- Nonces expire after 24 hours by default
- Nonce action names must be unique per operation
- Nonces do NOT replace capability checks (they are complementary)

### QA Criteria

- [ ] `wp_create_nonce()` used with unique action names
- [ ] `wp_verify_nonce()` called BEFORE any business logic
- [ ] `wp_nonce_url()` or `wp_nonce_field()` for URL-based actions
- [ ] Nonces are NOT a substitute for capability checks

---

### 3.4 Capability Checks and Access Control

Always check capabilities, never role names.

#### Capability Check Pattern

```php
<?php
// WRONG: Checking role names
if ('administrator' === $user->roles[0]) { // WRONG

// CORRECT: Checking capabilities
if (current_user_can('manage_options')) { // CORRECT

// CORRECT: Custom capability
if (current_user_can('edit_my_plugin_settings')) {
```

#### Custom Capabilities

```php
<?php
// During activation, add custom capabilities to administrator role
function my_plugin_activate(): void {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_my_plugin_settings');
        $role->add_cap('edit_my_plugin_data');
    }
}
register_activation_hook(__FILE__, 'my_plugin_activate');
```

#### Meta Capabilities for Object-Level Authorization

```php
<?php
// For post-specific capabilities
if (current_user_can('edit_post', $post_id)) {
    // User can edit this specific post
}

// Check current page context carefully
if (is_admin() && current_user_can('manage_options')) {
    // Safe to show admin page
}
```

### QA Criteria

- [ ] `current_user_can()` used instead of role name checks
- [ ] Custom capabilities added during activation
- [ ] Object-level authorization uses meta capabilities (e.g., `edit_post`, $post_id)
- [ ] `is_admin()` NOT used as a security check (it only checks URL path)

---

### 3.5 REST API Authentication and Permissions

Every REST endpoint MUST have a permission callback.

#### Required Permission Callback

```php
<?php
add_action('rest_api_init', function() {
    register_rest_route('my-plugin/v1', '/data', [
        'methods' => 'GET',
        'callback' => function(\WP_REST_Request $request) {
            return ['data' => 'value'];
        },
        'permission_callback' => function(\WP_REST_Request $request): bool|\WP_Error {
            if (!current_user_can('read_my_plugin_data')) {
                return new \WP_Error(
                    'rest_forbidden',
                    esc_html__('You do not have permission to access this data.', 'my-plugin'),
                    ['status' => 403]
                );
            }
            return true;
        },
    ]);
});
```

#### Common Mistakes

```php
<?php
// WRONG: is_user_logged_in() allows any subscriber
'permission_callback' => function() {
    return is_user_logged_in(); // WRONG - subscribers can access
}

// CORRECT: Use capability
'permission_callback' => function() {
    return current_user_can('edit_posts'); // Appropriate capability
}
```

### QA Criteria

- [ ] Every `register_rest_route()` has `permission_callback` defined
- [ ] `permission_callback` uses `current_user_can()` not `is_user_logged_in()`
- [ ] Returns `WP_Error` with status code 403 for unauthorized requests
- [ ] Authentication and authorization are both addressed

---

## 4. Performance Optimization

### 4.1 Database Query Optimization

#### WP_Query Optimization Flags

```php
<?php
// OPTIMIZED: Fetch only IDs
$query = new \WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 10,
    'fields' => 'ids',           // Skip full post object loading
    'no_found_rows' => true,      // Disable pagination calculation
    'update_post_meta_cache' => false,  // Skip term cache updates
    'update_post_term_cache' => false,
]);
$post_ids = $query->posts;

// Later, if needed, fetch full objects selectively
// foreach ($post_ids as $id) {
//     $post = get_post($id);
//     // ...
// }
```

#### Query Optimization Principles

| Technique | Benefit |
|-----------|---------|
| `'fields' => 'ids'` | Skip post object hydration |
| `'no_found_rows' => true` | Skip FOUND_ROWS() SQL query |
| `'update_post_meta_cache' => false` | Skip metadata cache fill |
| `'update_post_term_cache' => false` | Skip term cache fill |
| Taxonomy queries over meta_query | Taxonomies are indexed; meta is not |

#### N+1 Query Prevention

```php
<?php
// WRONG: N+1 query
foreach ($posts as $post) {
    $meta = get_post_meta($post->ID, 'price', true); // One query per post
}

// CORRECT: Use meta_query in primary query
$query = new \WP_Query([
    'meta_query' => [
        ['key' => 'price', 'value' => 100, 'compare' => '>=', 'type' => 'NUMERIC']
    ]
]);
```

#### Avoiding Antipattern Queries

```php
<?php
// NEVER use query_posts() - corrupts main query
query_posts($args); // WRONG

// AVOID: posts_per_page => -1 on archive pages
$query = new \WP_Query(['posts_per_page' => -1]); // WRONG for archives

// AVOID: Large post__not_in arrays
$exclude = array_map('absint', $_POST['exclude_ids']);
if (count($exclude) > 100) {
    // Chunk and process in batches, or use taxonomy filtering instead
}
```

### 4.1.1 Custom Tables vs. Native Storage Decision Matrix

Use WordPress native storage (post meta, options, terms) until scale demands otherwise.

| Data Type | Native Storage | Custom Table Threshold |
|-----------|---------------|----------------------|
| Post metadata | `postmeta` table | >1,000 posts with same meta key queried together |
| User metadata | `usermeta` table | >10,000 users with same meta |
| Site options | `options` table | <100 options, all load on every request |
| Hierarchical post data | `posts` + `postmeta` | Deeply nested trees >1,000 nodes |
| Time-series data | `postmeta` or custom | >100,000 records with date range queries |
| Many-to-many relationships | `term_relationships` | >10,000 relationships with complex taxonomy queries |

#### When to Create Custom Tables

```php
<?php
// Indications for custom table:
$should_use_custom_table = (
    // Volume threshold exceeded
    $record_count > 100000
    // OR Complex relational queries WordPress can't optimize
    || ($needs_joins && $join_count > 3)
    // OR Specific indexing needs beyond postmeta
    || ($needs_fulltext_index || $needs_geospatial)
    // OR Data isolation required (not tied to posts/users/terms)
    && !$is_post_related && !$is_user_related && !$is_term_related
);
```

#### Custom Table with dbDelta

```php
<?php
function my_plugin_create_custom_table(): void {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // CRITICAL: Two spaces after PRIMARY KEY for dbDelta compatibility
    $sql = "CREATE TABLE {$wpdb->prefix}my_plugin_events (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        post_id bigint(20) DEFAULT NULL,
        user_id bigint(20) DEFAULT NULL,
        event_data longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY event_type (event_type),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

### QA Criteria

- [ ] Native storage used by default; custom tables only when justified
- [ ] Volume thresholds documented and reviewed at scale
- [ ] dbDelta spacing requirements observed (two spaces after PRIMARY KEY)
- [ ] Custom tables use `$wpdb->prefix`
- [ ] Proper indexes created for query patterns
- [ ] Uninstall routine deletes custom tables

---

### QA Criteria

- [ ] `query_posts()` never used
- [ ] `'fields' => 'ids'` used when full post objects not needed
- [ ] `'no_found_rows' => true` used when pagination not required
- [ ] `meta_query` used instead of `get_post_meta()` in loops
- [ ] `posts_per_page` has reasonable limits (not -1 on archives)

---

### 4.2 Caching Strategies

#### When to Use Each Cache Type

| Cache Type | Use Case | Lifetime |
|------------|----------|----------|
| `wp_cache_*` | Single request, in-memory | Request only |
| `set_transient` | Persistent, expiring data | Until expiration |
| `update_option` | Permanent settings | Until changed |

#### Transient Pattern with Regeneration Fallback

```php
<?php
function my_plugin_get_api_data(): array {
    $cache_key = 'my_plugin_api_data';
    $data = get_transient($cache_key);

    if (false === $data) {
        // Cache miss - fetch fresh data
        $response = wp_remote_get('https://api.example.com/data', [
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            // On error, return stale cache if available
            return $data ?? [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_wp_error($data)) {
            set_transient($cache_key, $data, HOUR_IN_SECONDS);
        }
    }

    return $data;
}
```

#### Object Cache Pattern

```php
<?php
function my_plugin_get_processed_data(int $id): array {
    $cache_key = "my_plugin_processed_{$id}";
    $data = wp_cache_get($cache_key, 'my_plugin');

    if (false === $data) {
        $data = my_plugin_expensive_computation($id);
        wp_cache_set($cache_key, $data, 'my_plugin', HOUR_IN_SECONDS);
    }

    return $data;
}
```

### QA Criteria

- [ ] `wp_cache_*` used for single-request caching
- [ ] `set_transient`/`get_transient` used for persistent caching
- [ ] Cache miss regeneration fallback implemented
- [ ] Options with `autoload='yes'` limited (loaded on every page)
- [ ] No caching of permanent data in transients

---

### 4.3 Memory Management and Lazy Loading

#### Contextual Initialization

```php
<?php
class My_Plugin {
    private bool $admin_loaded = false;
    private bool $public_loaded = false;

    public function init(): void {
        // Only load what is needed per request
        if (is_admin()) {
            $this->load_admin();
        } elseif (wp_is_json_request()) {
            $this->load_api();
        } elseif (wp_doing_cron()) {
            $this->load_cron();
        } elseif (defined('WP_CLI') && WP_CLI) {
            $this->load_cli();
        } else {
            $this->load_public();
        }
    }

    private function load_admin(): void {
        if ($this->admin_loaded) {
            return;
        }
        require_once MY_PLUGIN_PATH . 'admin/class-admin.php';
        new Admin();
        $this->admin_loaded = true;
    }
}
```

#### Service Container Lazy Loading

```php
<?php
class Plugin_Service_Container {
    private array $services = [];
    private array $resolved = [];

    public function get_service(string $name) {
        if (!isset($this->resolved[$name])) {
            if (isset($this->services[$name])) {
                $this->resolved[$name] = $this->services[$name]($this);
            } else {
                throw new \RuntimeException("Service {$name} not registered");
            }
        }
        return $this->resolved[$name];
    }
}
```

#### Memory Profiling

```php
<?php
// Log memory usage during development
$start_memory = memory_get_usage();
my_plugin_expensive_operation();
$end_memory = memory_get_usage();
error_log(sprintf(
    'Memory delta: %s KB (peak: %s KB)',
    number_format(($end_memory - $start_memory) / 1024, 2),
    number_format(memory_get_peak_usage(true) / 1024, 2)
));
```

### QA Criteria

- [ ] Plugin checks `is_admin()`, `wp_is_json_request()`, `wp_doing_cron()`, `defined('WP_CLI')` before loading components
- [ ] No full plugin initialization on every request
- [ ] Large objects unset when no longer needed
- [ ] Lazy loading implemented via Service Container or equivalent

---

## 5. Asset Management

### Enqueue Scripts and Styles Properly

```php
<?php
add_action('wp_enqueue_scripts', function() {
    // Conditional loading - only on pages that need assets
    if (!is_singular() && !has_shortcode(get_the_content(), 'my_shortcode')) {
        return;
    }

    $css_ver = filemtime(MY_PLUGIN_DIR . 'build/frontend.css');
    $js_ver = filemtime(MY_PLUGIN_DIR . 'build/frontend.js');

    wp_enqueue_style('my-plugin-frontend', MY_PLUGIN_URL . 'build/frontend.css', [], $css_ver);
    wp_enqueue_script('my-plugin-frontend', MY_PLUGIN_URL . 'build/frontend.js', ['jquery'], $js_ver, true);

    // Static data - use wp_localize_script
    wp_localize_script('my-plugin-frontend', 'MyPluginData', [
        'apiUrl' => rest_url('my-plugin/v1/'),
        'pluginVersion' => MY_PLUGIN_VERSION,
    ]);
});

// Dynamic data (nonce, user-specific) - use wp_add_inline_script AFTER the dependent script
add_action('wp_enqueue_scripts', function() {
    if (!wp_script_is('my-plugin-frontend', 'enqueued')) {
        return;
    }
    wp_add_inline_script(
        'my-plugin-frontend',
        'MyPluginData.nonce = "' . wp_create_nonce('my_plugin_nonce') . '";',
        'after'
    );
});
```

### Admin Asset Enqueueing

```php
<?php
add_action('admin_enqueue_scripts', function($hook_suffix) {
    // Only load on specific admin pages
    if ('toplevel_page_my-plugin' !== $hook_suffix) {
        return;
    }

    wp_enqueue_style('my-plugin-admin', MY_PLUGIN_URL . 'admin/assets/css/admin.css', [], filemtime(MY_PLUGIN_DIR . 'admin/assets/css/admin.css'));
    wp_enqueue_script('my-plugin-admin', MY_PLUGIN_URL . 'admin/assets/js/admin.js', ['jquery'], filemtime(MY_PLUGIN_DIR . 'admin/assets/js/admin.js'), true);
});
```

### QA Criteria

- [ ] `wp_enqueue_script()` and `wp_enqueue_style()` used (never hardcoded tags)
- [ ] Conditional loading based on page context
- [ ] Version using `filemtime()` for cache busting
- [ ] Dependencies declared in `$deps` array
- [ ] `wp_localize_script()` for static data, `wp_add_inline_script()` for dynamic data

---

## 6. Internationalization and Localization

### Text Domain Setup

```php
<?php
// In the main plugin file, load text domain early
function my_plugin_load_textdomain(): void {
    load_plugin_textdomain(
        'my-plugin',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'my_plugin_load_textdomain');
```

### Translation Functions

| Function | Use Case |
|----------|----------|
| `__()` | Standard translation lookup |
| `_e()` | Echo translation directly |
| `esc_html__()` | Translated text that is HTML-safe |
| `esc_html_e()` | Echo HTML-safe translation |
| `esc_attr__()` | Translated attribute text |
| `_x()` | Translation with context |
| `_n()` | Plural form translation |

### Translation-Friendly Development

```php
<?php
// WRONG: String concatenation breaks translations
echo 'Hello ' . $name;

// CORRECT: Full string for translation
echo esc_html(sprintf(
    /* translators: %s: User name */
    __('Hello %s', 'my-plugin'),
    $name
));

// With context
echo esc_html(_x('Edit', 'verb', 'my-plugin'));
echo esc_html(_x('Edit', 'noun', 'my-plugin'));

// Plural forms
$count = absint($_POST['count']);
echo esc_html(sprintf(
    /* translators: %d: Number of items */
    _n(
        '%d item deleted',
        '%d items deleted',
        $count,
        'my-plugin'
    ),
    $count
));
```

### JavaScript Translation

```php
<?php
// For JS translation strings
wp_enqueue_script('my-plugin-admin');
wp_localize_script('my-plugin-admin', 'myPluginI18n', [
    'deleteConfirm' => __('Are you sure you want to delete this?', 'my-plugin'),
    'saveSuccess'   => __('Settings saved successfully.', 'my-plugin'),
    'ajaxError'     => __('An error occurred. Please try again.', 'my-plugin'),
]);
```

### QA Criteria

- [ ] Text domain loaded in `plugins_loaded` hook
- [ ] All user-facing strings wrapped in translation functions
- [ ] Translation functions used with `esc_html__()`, `esc_attr__()` for output
- [ ] No string concatenation inside translation functions
- [ ] JS strings passed via `wp_localize_script()`
- [ ] Text Domain in plugin header matches plugin slug
- [ ] `.pot` file generated and updated with each release

---

## 7. REST API Design

### Versioned Namespaces and Routes

```php
<?php
add_action('rest_api_init', function() {
    // Versioned namespace
    register_rest_route('my-plugin/v1', '/items', [
        'methods' => 'GET',
        'callback' => function(\WP_REST_Request $request): array {
            $per_page = absint($request->get_param('per_page') ?? 10);
            $page = absint($request->get_param('page') ?? 1);

            $query = new \WP_Query([
                'post_type' => 'my_cpt',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'fields' => 'ids',
                'no_found_rows' => true,
            ]);

            return [
                'data' => $query->posts,
                'meta' => [
                    'total' => (int) $query->found_posts,
                    'pages' => (int) $query->max_num_pages,
                ],
            ];
        },
        'permission_callback' => function(): bool {
            return current_user_can('read');
        },
        'args' => [
            'per_page' => [
                'default' => 10,
                'sanitize_callback' => 'absint',
            ],
            'page' => [
                'default' => 1,
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);

    register_rest_route('my-plugin/v1', '/items/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => function(\WP_REST_Request $request): array|\WP_Error {
            $id = absint($request->get_param('id'));
            $item = get_post($id);

            if (!$item || 'my_cpt' !== $item->post_type) {
                return new \WP_Error('not_found', 'Item not found', ['status' => 404]);
            }

            return ['data' => $item];
        },
        'permission_callback' => function(): bool {
            return current_user_can('read');
        },
    ]);
});
```

### Standard HTTP Status Codes

| Code | Use |
|------|-----|
| 200 | Successful GET |
| 201 | Successful POST (created) |
| 400 | Bad request (invalid parameters) |
| 401 | Unauthorized (not authenticated) |
| 403 | Forbidden (authenticated but not permitted) |
| 404 | Resource not found |
| 500 | Server error |

### QA Criteria

- [ ] Routes use versioned namespace (`/my-plugin/v1/`)
- [ ] Every route has `permission_callback` defined
- [ ] Arguments have `sanitize_callback` defined
- [ ] Appropriate HTTP status codes returned
- [ ] WP_Error used for error responses

---

## 8. Plugin Lifecycle: Activation, Deactivation, Uninstall

### Lifecycle Responsibilities

| Phase | Do | Do NOT |
|-------|-----|--------|
| **Activation** | Create custom tables (via dbDelta), set default options, add capabilities, register cron schedules, flush rewrite rules | Complex business logic, network requests, create posts |
| **Deactivation** | Flush rewrite rules, unschedule all cron events | Delete any data |
| **Uninstall** | Delete ALL plugin data: options, transients, post meta, custom tables, uploaded files | Leave any data behind |

### Activation Hook

```php
<?php
function my_plugin_activate(): void {
    // Create custom table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$wpdb->prefix}my_plugin_data (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        value text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

    dbDelta($sql);

    // Set default options
    update_option('my_plugin_version', MY_PLUGIN_VERSION);
    update_option('my_plugin_settings', ['enabled' => true]);

    // Add capabilities
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_my_plugin');
    }

    // Register cron schedule
    if (!wp_next_scheduled('my_plugin_daily_cron')) {
        wp_schedule_event(time(), 'daily', 'my_plugin_daily_cron');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'my_plugin_activate');
```

### Deactivation Hook

```php
<?php
function my_plugin_deactivate(): void {
    // Clear scheduled cron events
    $timestamp = wp_next_scheduled('my_plugin_daily_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'my_plugin_daily_cron');
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'my_plugin_deactivate');
```

### Uninstall Hook (uninstall.php)

```php
<?php
declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('my_plugin_version');
delete_option('my_plugin_settings');

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_my_plugin_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_my_plugin_%'");

// Delete post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'my_plugin_%'");

// Delete custom table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}my_plugin_data");

// Delete uploaded files (recursive)
$upload_dir = WP_CONTENT_DIR . '/uploads/my-plugin/';
if (is_dir($upload_dir)) {
    array_map('unlink', glob("$upload_dir/*.*"));
    rmdir($upload_dir);
}
```

### QA Criteria

- [ ] `uninstall.php` used (NOT deactivation hook) for data deletion
- [ ] Activation hook only contains one-time setup
- [ ] Deactivation hook does NOT delete data
- [ ] All created data (tables, options, caps, cron) is properly cleaned on uninstall

---

## 9. Hook System Best Practices

### Register Hooks in Dedicated Methods

```php
<?php
class My_Plugin {
    public function register(): void {
        $this->register_actions();
        $this->register_filters();
        $this->register_shortcodes();
    }

    private function register_actions(): void {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    private function register_filters(): void {
        add_filter('the_content', [$this, 'filter_content']);
        add_filter('wp_head', [$this, 'maybe_inject_header'], 1);
    }

    private function register_shortcodes(): void {
        add_shortcode('my_shortcode', [$this, 'render_shortcode']);
    }
}
```

### Hook Naming Convention

```php
<?php
// Use plugin-prefixed hook names to avoid collisions
add_filter('my_plugin_before_save', [$this, 'before_save']);
add_action('my_plugin_after_export', [$this, 'after_export']);

// CORRECT: Descriptive, prefixed names
add_filter('my_plugin_content_filter', fn($content) => $content);

// WRONG: Generic names
add_filter('content_filter', fn($content) => $content); // Collision likely
```

### Priority and Argument Guidelines

```php
<?php
// Default priority 10 is fine for most hooks
add_action('init', [$this, 'init']); // priority 10, 1 argument

// Use lower priority (1-9) for early hooks that need to run first
add_action('wp_loaded', [$this, 'late_init'], 1); // Runs very late

// Use higher priority (100+) for hooks that should run after others
add_action('wp_head', [$this, 'inject_analytics'], 999); // Runs very late

// Explicit argument count when needed
add_filter('the_content', [$this, 'filter_content'], 10, 1); // 1 argument explicitly
```

### Hook Priority Ranges

| Range | Use Case |
|-------|----------|
| 1-8 | System-level hooks (rarely used) |
| 9-11 | Early initialization |
| 12-99 | Standard plugin initialization |
| 100+ | Late hooks, after most plugins have loaded |

### QA Criteria

- [ ] Hooks registered in dedicated `register_*` methods
- [ ] Plugin-prefixed hook names used
- [ ] Priority explicitly set when order matters
- [ ] Object-oriented callbacks `[$this, 'method']` used instead of closures in class context

---

## 10. Managed Hosting and WP Engine Constraints

### Key Differences from Standard WordPress

WP Engine uses Nginx (not Apache), which means:
- `.htaccess` files are **completely ignored**
- Standard Apache rewrite rules do not work

### Required Adaptations

```php
<?php
// WRONG: .htaccess rules (ignored on WP Engine)
# AddType application/x-httpd-php .php  (IGNORED)

// CORRECT: Use WordPress APIs for everything
// File type handling must be done in PHP code
```

### Cron Handling

```php
<?php
// WP Engine alternate cron runs every 60 seconds
// For sub-minute precision, use Action Scheduler
// https://actionscheduler.org/

if (defined('WP_CLI') && WP_CLI) {
    // CLI cron bypasses timing issues
    WP_CLI::runcommand('cron event run --due-now');
}
```

### File Write Restrictions

```php
<?php
// File writes ONLY in plugin directory or wp-content/uploads/
$plugin_dir = __DIR__; // Safe
$uploads_dir = wp_upload_dir()['basedir'] . '/my-plugin/'; // Safe
$uploads_dir = WP_CONTENT_DIR . '/uploads/my-plugin/'; // Safe

// WRONG: These will fail on WP Engine
// file_put_contents('/tmp/something.txt', $data); // FAIL
// file_put_contents(WP_CONTENT_DIR . '/cache/something.txt', $data); // FAIL
```

### Restricted Functions on WP Engine

The following should be avoided:
- `xmlrpc_*` functions (use REST API instead)
- Direct cron spawning via `wp-cron.php`
- `.htaccess` modifications
- `rename()` on large directories (distributed filesystem issues)

### QA Criteria

- [ ] No `.htaccess` reliance for functionality
- [ ] `xmlrpc` not used (REST API used instead)
- [ ] File writes restricted to plugin directory or uploads
- [ ] Cron handles 60-second precision (or uses Action Scheduler)

---

## 11. Compatibility: Multisite and Other Plugins

### Prefix Everything

```php
<?php
// Prefix: functions, classes, options, transient names, database tables
// Option names
update_option('my_plugin_settings', $settings);
get_transient('my_plugin_cache_' . $id);

// Database tables
$table_name = $wpdb->prefix . 'my_plugin_data';

// Hook names
add_action('my_plugin_after_save', ...);

// Class names (via PSR-4 namespace)
namespace My_Plugin\Controllers;
```

### Multisite Compatibility

```php
<?php
function my_plugin_get_option(string $key, $default = false) {
    if (is_multisite()) {
        return get_site_option($key, $default);
    }
    return get_option($key, $default);
}

function my_plugin_update_option(string $key, $value): bool {
    if (is_multisite()) {
        return update_site_option($key, $value);
    }
    return update_option($key, $value);
}

// Custom tables: always use $wpdb->prefix
$table = $wpdb->prefix . 'my_plugin_data';
```

### Plugin Compatibility Testing

- Test with popular plugins activated (Yoast SEO, WooCommerce, Advanced Custom Fields)
- Verify no function name collisions (prefix everything)
- Verify no hook name collisions (prefix everything)
- Verify CSS/JS loading doesn't conflict

### QA Criteria

- [ ] All functions, classes, options prefixed with plugin slug
- [ ] `$wpdb->prefix` used instead of hardcoded `wp_`
- [ ] `is_multisite()` checks for network-aware operations
- [ ] Tested with common third-party plugins

---

## 12. Code Quality: Standards and Testing

### WordPress Coding Standards (WPCS)

```bash
# Install WPCS
composer require --dev wordpress/wordpress-coding-standards

# Run PHPCS
./vendor/bin/phpcs --standard=WordPress-Core --extensions=php includes/

# Run with extra ruleset
./vendor/bin/phpcs --standard=WordPress-Core,WordPress-Extra --extensions=php includes/
```

### PHPCS Configuration (phpcs.xml)

```xml
<?xml version="1.0"?>
<ruleset name="My Plugin">
    <description>WordPress Coding Standards for My Plugin</description>
    <file>./includes/</file>
    <file>./admin/</file>
    <file>./public/</file>

    <rule ref="WordPress-Core"/>
    <rule ref="WordPress-Extra"/>

    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>
    <exclude-pattern>*/build/*</exclude-pattern>
</ruleset>
```

### Static Analysis (PHPStan)

```bash
composer require --dev phpstan/phpstan phpstan/extension-installer
```

```yaml
# phpstan.neon
parameters:
    level: 5
    paths:
        - includes/
        - admin/
        - public/
    stubFiles:
        - vendor/php-stubs/wordpress-stubs/wordpress-stubs.php
    constants:
        ABSPATH: '/var/www/html/'
```

### PHPUnit Testing

```bash
composer require --dev phpunit/phpunit wordpress/wordpress
```

```php
<?php
// tests/Test_Data_Service.php
namespace My_Plugin\Tests;

class Test_Data_Service extends \WP_UnitTestCase {
    private \My_Plugin\Data_Service $data_service;

    public function setUp(): void {
        parent::setUp();
        $this->data_service = new \My_Plugin\Data_Service();
    }

    public function test_sanitize_input_returns_clean_data(): void {
        $input = '<script>alert("xss")</script>Hello World';
        $result = $this->data_service->sanitize($input);
        $this->assertNotContains('<script>', $result);
    }

    public function test_get_post_meta_returns_correct_value(): void {
        $post_id = $this->factory->post->create();
        update_post_meta($post_id, 'test_key', 'test_value');

        $result = $this->data_service->get_post_meta($post_id, 'test_key');
        $this->assertSame('test_value', $result);
    }
}
```

### QA Criteria

- [ ] PHPCS runs with zero errors (warnings minimized)
- [ ] PHPStan level 5+ passes with WordPress stubs
- [ ] Unit test coverage for all service classes
- [ ] Integration tests for hooks, shortcodes, REST endpoints
- [ ] All tests pass in CI pipeline

---

## 13. Design Patterns

### Repository Pattern

Encapsulate all database access in repository classes.

```php
<?php
namespace My_Plugin\Repositories;

class Post_Repository {
    public function find_by_id(int $post_id): ?\WP_Post {
        $post = get_post($post_id);
        return $post instanceof \WP_Post ? $post : null;
    }

    public function find_by_meta(string $key, $value): array {
        $query = new \WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => $key, 'value' => $value]
            ],
            'fields' => 'ids',
        ]);
        return array_map('get_post', $query->posts);
    }

    public function create(array $data): int {
        return wp_insert_post($data);
    }

    public function update(int $post_id, array $data): bool {
        $data['ID'] = $post_id;
        return (bool) wp_update_post($data);
    }

    public function delete(int $post_id): bool {
        return (bool) wp_delete_post($post_id);
    }
}
```

### Dependency Injection

Pass dependencies via constructor or setter, never access global singletons.

```php
<?php
class My_Plugin_Service {
    private Post_Repository $repository;
    private Cache_Service $cache;

    public function __construct(Post_Repository $repository, Cache_Service $cache) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    public function get_data(int $id): ?array {
        $cache_key = "my_plugin_data_{$id}";
        $data = $this->cache->get($cache_key);

        if (null === $data) {
            $post = $this->repository->find_by_id($id);
            $data = $post ? $post->to_array() : null;
            $this->cache->set($cache_key, $data, HOUR_IN_SECONDS);
        }

        return $data;
    }
}
```

### Service Container (Recommended over Singleton)

```php
<?php
class Service_Container {
    private static ?self $instance = null;
    private array $services = [];
    private array $resolved = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $name, callable $factory): void {
        $this->services[$name] = $factory;
    }

    public function get(string $name) {
        if (!isset($this->resolved[$name])) {
            $this->resolved[$name] = $this->services[$name]($this);
        }
        return $this->resolved[$name];
    }

    private function __construct() {
        $this->register_default_services();
    }

    private function register_default_services(): void {
        $this->register('repository', fn($c) => new Post_Repository());
        $this->register('cache', fn($c) => new Cache_Service());
        $this->register('service', fn($c) => new My_Plugin_Service(
            $c->get('repository'),
            $c->get('cache')
        ));
    }
}
```

### QA Criteria

- [ ] Repository pattern used for all database access
- [ ] Dependencies injected via constructor/setter
- [ ] Service Container used instead of Singleton for main plugin class
- [ ] No global mutable state

---

## 14. Error Handling and Logging

### Exception Handling Pattern

```php
<?php
class My_Plugin_Exception extends \RuntimeException {
    protected string $error_code;

    public function __construct(string $message, string $error_code = 'unknown', int $code = 0, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
    }

    public function get_error_code(): string {
        return $this->error_code;
    }
}

class Data_Processing_Exception extends My_Plugin_Exception {
    public function __construct(string $message, int $post_id = 0, ?\Throwable $previous = null) {
        parent::__construct(
            $message,
            'data_processing_error',
            0,
            $previous
        );
        $this->context['post_id'] = $post_id;
    }
}
```

### Try-Catch with WordPress Error Handling

```php
<?php
function my_plugin_process_data(int $post_id): array {
    try {
        $post = get_post($post_id);
        if (!$post) {
            throw new My_Plugin_Exception(
                sprintf('Post %d not found', $post_id),
                'post_not_found'
            );
        }

        $result = my_plugin_do_processing($post);
        return ['success' => true, 'data' => $result];

    } catch (My_Plugin_Exception $e) {
        my_plugin_log_error($e->getMessage(), $e->get_error_code(), [
            'post_id' => $post_id,
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'error_code' => $e->get_error_code(),
        ];

    } catch (\Throwable $e) {
        // Unexpected error - log for debugging but don't expose details
        my_plugin_log_error(
            'Unexpected error in my_plugin_process_data',
            'unexpected_error',
            ['exception' => $e->getMessage()]
        );

        return [
            'success' => false,
            'error' => __('An unexpected error occurred.', 'my-plugin'),
            'error_code' => 'unexpected_error',
        ];
    }
}
```

### WordPress Debug Logging

```php
<?php
function my_plugin_log_error(string $message, string $code = '', array $context = []): void {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $log_entry = sprintf(
        '[%s] [%s] %s %s',
        date('Y-m-d H:i:s'),
        strtoupper($code),
        $message,
        !empty($context) ? wp_json_encode($context) : ''
    );

    error_log($log_entry);

    // Optional: write to custom log file
    $log_file = WP_CONTENT_DIR . '/debug.log';
    file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);
}
```

### Admin Notice Integration

```php
<?php
function my_plugin_show_admin_error(string $message, string $type = 'error'): void {
    add_action('admin_notices', function() use ($message, $type) {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($type),
            esc_html($message)
        );
    });
}
```

### REST API Error Responses

```php
<?php
add_action('rest_api_init', function() {
    register_rest_route('my-plugin/v1', '/process', [
        'methods' => 'POST',
        'callback' => function(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
            try {
                $post_id = absint($request->get_param('post_id'));
                $result = my_plugin_process_data($post_id);

                if (!$result['success']) {
                    return new \WP_Error(
                        $result['error_code'],
                        $result['error'],
                        ['status' => 400]
                    );
                }

                return new \WP_REST_Response($result['data'], 200);

            } catch (\Throwable $e) {
                return new \WP_Error(
                    'server_error',
                    __('An error occurred processing your request.', 'my-plugin'),
                    ['status' => 500]
                );
            }
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);
});
```

### QA Criteria

- [ ] Custom exception classes for plugin-specific errors
- [ ] All external operations wrapped in try-catch
- [ ] Error messages logged to debug log when `WP_DEBUG` is true
- [ ] User-facing errors use generic messages (no stack traces)
- [ ] REST endpoints return appropriate HTTP status codes
- [ ] Errors include machine-readable error codes for API consumers
- [ ] `error_log()` used instead of `var_dump()` or `print_r()`

---

## 15. Antipattern Catalog

### Critical Security Antipatterns

| Antipattern | Consequence | Prevention |
|-------------|-------------|------------|
| Direct SQL without `$wpdb->prepare()` | SQL Injection | Always use `$wpdb->prepare()` |
| Unsanitized `$_POST`/`$_GET` | XSS, RCE | Always sanitize with WordPress functions |
| Missing nonces on state-changing requests | CSRF | Use `wp_create_nonce()`/`wp_verify_nonce()` |
| No capability checks | Unauthorized access | `current_user_can()` on every request |
| `is_admin()` as security check | Bypass via URL manipulation | Use `current_user_can()` |
| REST endpoint without `permission_callback` | Public data exposure | Always define `permission_callback` |

### Critical Performance Antipatterns

| Antipattern | Consequence | Prevention |
|-------------|-------------|------------|
| `query_posts()` | Query corruption, broken pagination | Use `new WP_Query()` |
| `posts_per_page => -1` on archives | Memory exhaustion | Set reasonable limits |
| `get_post_meta()` in loop | N+1 query problem | Use `meta_query` in primary query |
| No caching for expensive operations | Server overload | Use transients/object cache |
| Loading assets on every page | Page bloat | Conditional enqueueing |

### Critical Architecture Antipatterns

| Antipattern | Consequence | Prevention |
|-------------|-------------|------------|
| Global variable pollution | Naming collisions | Namespaces + autoloading |
| No autoloading | Fragile code | PSR-4 Composer autoloading |
| Singleton for all classes | Hidden dependencies, untestable | Service Container |
| Business logic in hooks | Untestable | Separate service classes |

### Critical Hosting Antipatterns

| Antipattern | Consequence | Prevention |
|-------------|-------------|------------|
| `.htaccess` reliance | Broken on Nginx (WP Engine) | WordPress APIs only |
| `xmlrpc_*` usage | Blocked on WP Engine | REST API instead |
| File writes to uploads/outside plugin | Security/capacity issues | Plugin directory only |
| Direct `wp-cron.php` calls | Firewall blocks | `DISABLE_WP_CRON`, system cron |

### QA Criteria

- [ ] No antipatterns in code (use automated scanning)
- [ ] PHPCS catches coding standard violations
- [ ] PHPStan catches type safety issues
- [ ] Tests cover antipattern avoidance

---

## 16. WordPress.org Review Compliance

### Required readme.txt Sections

```
=== Plugin Name ===
Contributors: username
Tags: tag1, tag2
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Short description of what the plugin does.

== Installation ==
Step-by-step installation instructions.

== Frequently Asked Questions ==
Common questions and answers.

== Changelog ==
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.0 =
Initial release.
```

### GPL Compliance

- License must be GPLv2 or later
- All code must be GPL-compatible
- Third-party libraries must have compatible licenses
- Declare any proprietary or non-free components

### Required Plugin Header

```php
<?php
/*
 * Plugin Name:       My Awesome Plugin
 * Plugin URI:        https://example.com/my-awesome-plugin
 * Description:       A brief description of what the plugin does.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-awesome-plugin
 * Domain Path:       /languages
 */
```

### QA Criteria

- [ ] `readme.txt` contains all required sections
- [ ] Plugin header has all required fields
- [ ] License is GPLv2+ and compatible
- [ ] No non-free third-party dependencies
- [ ] `Text Domain` matches plugin slug

---

## Appendix A: Consolidated Security Checklist

- [ ] All superglobals sanitized before use
- [ ] `$wpdb->prepare()` used for all SQL queries
- [ ] Context-specific escaping at output time
- [ ] Nonces created and verified for all state-changing requests
- [ ] `current_user_can()` used (not role checks)
- [ ] REST endpoints have `permission_callback`
- [ ] No direct file inclusion via user input
- [ ] No debug code in production

## Appendix B: Consolidated Performance Checklist

- [ ] `query_posts()` never used
- [ ] `WP_Query` with optimized flags (`fields`, `no_found_rows`)
- [ ] Caching implemented for expensive operations
- [ ] Lazy loading for admin-only or conditional functionality
- [ ] Assets conditionally enqueued
- [ ] No N+1 queries

## Appendix C: Consolidated Architecture Checklist

- [ ] PSR-4 autoloading via Composer
- [ ] Single entry point with plugin header
- [ ] Service Container (not Singleton)
- [ ] Repository pattern for data access
- [ ] Direct access protection in all PHP files
- [ ] Full lifecycle management (activation/deactivation/uninstall)

---

*Report synthesized from 3 independent research sources: Perplexity, Qwen, and Gemini. Conflicts resolved via majority rule or synthesis. All guidance aligned with WordPress Plugin Handbook, OWASP standards, and WordPress Coding Standards.*
