<?php
/**
 * Theme Assets Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

use CrispyTheme\Theme\Assets;
use Brain\Monkey\Functions;

// The booking page and home page checks run inside enqueue_frontend_assets(). These tests are not on that page.
beforeEach(function () {
    Functions\when('is_page')->justReturn(false);
    Functions\when('is_front_page')->justReturn(false);
    Functions\when('get_option')->alias(static fn(string $name, mixed $default = false): mixed => $default);
});

describe('Assets::init', function () {
    it('registers wp_enqueue_scripts action for enqueue_frontend_assets', function () {
        $this->setupHooks();

        $assets = new Assets();
        $assets->init();

        $this->assertActionRegistered('wp_enqueue_scripts', [$assets, 'enqueue_frontend_assets']);
    });

    it('registers enqueue_block_editor_assets action for enqueue_editor_assets', function () {
        $this->setupHooks();

        $assets = new Assets();
        $assets->init();

        $this->assertActionRegistered('enqueue_block_editor_assets', [$assets, 'enqueue_editor_assets']);
    });

    it('registers admin_enqueue_scripts action for enqueue_admin_assets', function () {
        $this->setupHooks();

        $assets = new Assets();
        $assets->init();

        $this->assertActionRegistered('admin_enqueue_scripts', [$assets, 'enqueue_admin_assets']);
    });

    it('registers wp_head action for add_dark_mode_script at priority 1', function () {
        $this->setupHooks();

        $assets = new Assets();
        $assets->init();

        $this->assertActionRegistered('wp_head', [$assets, 'add_dark_mode_script'], 1);
    });

    it('registers wp_head action for add_layout_custom_properties at priority 5', function () {
        $this->setupHooks();

        $assets = new Assets();
        $assets->init();

        $this->assertActionRegistered('wp_head', [$assets, 'add_layout_custom_properties'], 5);
    });
});

describe('Assets::enqueue_frontend_assets when singular', function () {
    beforeEach(function () {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('admin_url')->alias(
            static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
        );
        Functions\when('wp_create_nonce')->justReturn('test-nonce-abc');
        Functions\when('__')->returnArg(1);
    });

    it('enqueues main theme stylesheet', function () {
        $enqueuedStyles = [];

        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedStyles)->toContain('crispy-theme-style');
    });

    it('enqueues all expected frontend stylesheets', function () {
        $enqueuedStyles = [];

        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        $expectedStyles = [
            'crispy-theme-style',
            'crispy-theme-header',
            'crispy-theme-markdown',
            'crispy-theme-markdown-dark',
            'crispy-theme-patterns',
            'crispy-theme-newsletter',
            'crispy-theme-code-showcase',
            'crispy-theme-layout',
        ];

        foreach ($expectedStyles as $style) {
            expect($enqueuedStyles)->toContain($style);
        }
    });

    it('enqueues prism assets when singular', function () {
        $enqueuedScripts = [];

        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedScripts)->toContain('crispy-theme-prism');
    });

    it('enqueues markdown dropdown script when singular', function () {
        $enqueuedScripts = [];

        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedScripts)->toContain('crispy-theme-markdown-dropdown');
    });

    it('enqueues dark mode toggle script', function () {
        $enqueuedScripts = [];

        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedScripts)->toContain('crispy-theme-dark-mode');
    });

    it('calls wp_localize_script for dark mode settings', function () {
        $localizedScripts = [];

        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->alias(
            static function (string $handle, string $objectName, array $data) use (&$localizedScripts): bool {
                $localizedScripts[$handle] = ['object' => $objectName, 'data' => $data];
                return true;
            }
        );

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($localizedScripts)->toHaveKey('crispy-theme-dark-mode');
        expect($localizedScripts['crispy-theme-dark-mode']['object'])->toBe('crispyThemeDarkMode');
        expect($localizedScripts['crispy-theme-dark-mode']['data'])->toHaveKey('storageKey');
    });
});

describe('Assets::enqueue_frontend_assets when not singular', function () {
    it('does not enqueue prism when not singular and filter returns false', function () {
        Functions\when('is_singular')->justReturn(false);
        Functions\when('apply_filters')->alias(
            static function (string $hook, mixed $value): mixed {
                if ($hook === 'crispytheme_load_prism') {
                    return false;
                }
                return $value;
            }
        );
        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->justReturn(true);
        Functions\when('__')->returnArg(1);

        $enqueuedScripts = [];
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedScripts)->not->toContain('crispy-theme-prism');
        expect($enqueuedScripts)->not->toContain('crispy-theme-markdown-dropdown');
    });

    it('enqueues prism via filter when not singular but filter returns true', function () {
        Functions\when('is_singular')->justReturn(false);
        Functions\when('apply_filters')->alias(
            static function (string $hook, mixed $value): mixed {
                if ($hook === 'crispytheme_load_prism') {
                    return true;
                }
                return $value;
            }
        );
        Functions\when('admin_url')->alias(
            static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
        );
        Functions\when('wp_create_nonce')->justReturn('nonce-xyz');
        Functions\when('__')->returnArg(1);

        $enqueuedScripts = [];
        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );
        Functions\when('wp_localize_script')->justReturn(true);

        $assets = new Assets();
        $assets->enqueue_frontend_assets();

        expect($enqueuedScripts)->toContain('crispy-theme-prism');
        expect($enqueuedScripts)->not->toContain('crispy-theme-markdown-dropdown');
    });
});

describe('Assets::enqueue_editor_assets', function () {
    it('calls add_editor_style with editor.css', function () {
        $capturedPath = null;

        Functions\when('add_editor_style')->alias(
            static function (string $path) use (&$capturedPath): void {
                $capturedPath = $path;
            }
        );
        Functions\when('wp_enqueue_style')->justReturn(null);

        $assets = new Assets();
        $assets->enqueue_editor_assets();

        expect($capturedPath)->toBe('assets/css/editor.css');
    });

    it('enqueues markdown editor style', function () {
        $enqueuedStyles = [];

        Functions\when('add_editor_style')->justReturn(null);
        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );

        $assets = new Assets();
        $assets->enqueue_editor_assets();

        expect($enqueuedStyles)->toContain('crispy-theme-markdown-editor');
    });
});

describe('Assets::enqueue_admin_assets', function () {
    it('enqueues admin preview assets for post.php hook', function () {
        $enqueuedStyles  = [];
        $enqueuedScripts = [];

        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );
        Functions\when('wp_localize_script')->justReturn(true);
        Functions\when('admin_url')->alias(
            static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
        );
        Functions\when('wp_create_nonce')->justReturn('preview-nonce');

        $assets = new Assets();
        $assets->enqueue_admin_assets('post.php');

        expect($enqueuedStyles)->toContain('crispy-theme-admin-preview');
        expect($enqueuedStyles)->toContain('crispy-theme-markdown-admin');
        expect($enqueuedScripts)->toContain('crispy-theme-admin-preview');
    });

    it('enqueues admin preview assets for post-new.php hook', function () {
        $enqueuedStyles = [];

        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->justReturn(true);
        Functions\when('admin_url')->alias(
            static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
        );
        Functions\when('wp_create_nonce')->justReturn('preview-nonce');

        $assets = new Assets();
        $assets->enqueue_admin_assets('post-new.php');

        expect($enqueuedStyles)->toContain('crispy-theme-admin-preview');
    });

    it('does not enqueue admin assets for unrelated hook suffix', function () {
        $enqueuedStyles  = [];
        $enqueuedScripts = [];

        Functions\when('wp_enqueue_style')->alias(
            static function (string $handle) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = $handle;
            }
        );
        Functions\when('wp_enqueue_script')->alias(
            static function (string $handle) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = $handle;
            }
        );

        $assets = new Assets();
        $assets->enqueue_admin_assets('index.php');

        expect($enqueuedStyles)->not->toContain('crispy-theme-admin-preview');
        expect($enqueuedScripts)->not->toContain('crispy-theme-admin-preview');
    });

    it('localizes script for admin preview with ajaxUrl and nonce', function () {
        $localizedData = null;

        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->alias(
            static function (string $handle, string $objectName, array $data) use (&$localizedData): bool {
                if ($handle === 'crispy-theme-admin-preview') {
                    $localizedData = $data;
                }
                return true;
            }
        );
        Functions\when('admin_url')->alias(
            static fn(string $path = ''): string => 'https://example.com/wp-admin/' . ltrim($path, '/')
        );
        Functions\when('wp_create_nonce')->justReturn('preview-nonce-123');

        $assets = new Assets();
        $assets->enqueue_admin_assets('post.php');

        expect($localizedData)->toHaveKey('ajaxUrl');
        expect($localizedData)->toHaveKey('nonce');
        expect($localizedData['nonce'])->toBe('preview-nonce-123');
    });
});

describe('Assets::add_dark_mode_script', function () {
    it('outputs a script tag to prevent FOUC', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        expect($output)->toContain('<script>');
        expect($output)->toContain('</script>');
    });

    it('contains the storage key in the script', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        expect($output)->toContain('crispy-theme-dark-mode');
    });

    it('contains localStorage logic for dark mode detection', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        expect($output)->toContain('localStorage');
        expect($output)->toContain('dark-mode');
        expect($output)->toContain('matchMedia(\'(prefers-color-scheme: dark)\')');
    });

    it('reads the key the toggle writes and the older key', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        // build/dark-mode-toggle.js writes 'true' or 'false' under crispy-dark-mode.
        expect($output)->toContain("const storageKey = 'crispy-dark-mode';");
        expect($output)->toContain("'true'");
        expect($output)->toContain("'false'");
        // The older head script read 'dark' or 'light' under crispy-theme-dark-mode.
        expect($output)->toContain("const legacyKey = 'crispy-theme-dark-mode';");
        expect($output)->toContain("'dark'");
        expect($output)->toContain("'light'");
    });

    it('prefers the toggle key and reads the older key only when it is unset', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        $toggleBranch = strpos($output, "stored === 'true' || stored === 'false'");
        $legacyBranch = strpos($output, "legacy === 'dark' || legacy === 'light'");

        expect($toggleBranch)->not->toBeFalse();
        expect($legacyBranch)->not->toBeFalse();
        expect($toggleBranch)->toBeLessThan($legacyBranch);
        expect($output)->toContain('} else if (legacy');
    });

    it('keeps the system preference when storage throws', function () {
        ob_start();
        $assets = new Assets();
        $assets->add_dark_mode_script();
        $output = ob_get_clean();

        expect($output)->toContain('try {');
        expect($output)->toContain('catch (e)');
    });

    it('passes the toggle key to the script settings', function () {
        $localized = [];

        Functions\when('is_singular')->justReturn(false);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('wp_enqueue_style')->justReturn(null);
        Functions\when('wp_enqueue_script')->justReturn(null);
        Functions\when('wp_localize_script')->alias(
            static function (string $handle, string $objectName, array $data) use (&$localized): bool {
                $localized[$handle] = $data;
                return true;
            }
        );

        (new Assets())->enqueue_frontend_assets();

        expect($localized['crispy-theme-dark-mode']['storageKey'])->toBe('crispy-dark-mode');
    });
});

describe('Assets::add_layout_custom_properties', function () {
    it('outputs style tag with default sidebar and max width vars', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        ob_start();
        $assets = new Assets();
        $assets->add_layout_custom_properties();
        $output = ob_get_clean();

        expect($output)->toContain('<style');
        expect($output)->toContain('--crispy-sidebar-width-base: 350px');
        expect($output)->toContain('--crispy-max-width-base: 1800px');
    });

    it('outputs configured valid sidebar and max width values', function () {
        Functions\when('get_option')->alias(
            static function (string $key, mixed $default = false): mixed {
                if ($key === 'crispytheme_sidebar_width') {
                    return '400px';
                }
                if ($key === 'crispytheme_max_width') {
                    return '2000px';
                }
                return $default;
            }
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        ob_start();
        $assets = new Assets();
        $assets->add_layout_custom_properties();
        $output = ob_get_clean();

        expect($output)->toContain('--crispy-sidebar-width-base: 400px');
        expect($output)->toContain('--crispy-max-width-base: 2000px');
    });

    it('falls back to defaults for invalid sidebar_width value', function () {
        Functions\when('get_option')->alias(
            static function (string $key, mixed $default = false): mixed {
                if ($key === 'crispytheme_sidebar_width') {
                    return 'invalid-value';
                }
                if ($key === 'crispytheme_max_width') {
                    return '1800px';
                }
                return $default;
            }
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        ob_start();
        $assets = new Assets();
        $assets->add_layout_custom_properties();
        $output = ob_get_clean();

        expect($output)->toContain('--crispy-sidebar-width-base: 350px');
    });

    it('falls back to default for invalid max_width value', function () {
        Functions\when('get_option')->alias(
            static function (string $key, mixed $default = false): mixed {
                if ($key === 'crispytheme_sidebar_width') {
                    return '350px';
                }
                if ($key === 'crispytheme_max_width') {
                    return 'bad-width';
                }
                return $default;
            }
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        ob_start();
        $assets = new Assets();
        $assets->add_layout_custom_properties();
        $output = ob_get_clean();

        expect($output)->toContain('--crispy-max-width-base: 1800px');
    });

    it('outputs style id attribute for crispy-layout-vars', function () {
        Functions\when('get_option')->alias(
            static fn(string $key, mixed $default = false): mixed => $default
        );
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        ob_start();
        $assets = new Assets();
        $assets->add_layout_custom_properties();
        $output = ob_get_clean();

        expect($output)->toContain('id="crispy-layout-vars"');
    });
});
