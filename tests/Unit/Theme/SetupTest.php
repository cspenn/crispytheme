<?php
/**
 * Theme Setup Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

use CrispyTheme\Theme\Setup;
use Brain\Monkey\Functions;

describe('Setup::init', function () {
    it('registers after_setup_theme action for setup_theme', function () {
        $this->setupHooks();

        Functions\when('add_filter')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerFilter($hook, $callback, $priority, $accepted_args);
            }
        );

        $setup = new Setup();
        $setup->init();

        $this->assertActionRegistered('after_setup_theme', [$setup, 'setup_theme']);
    });

    it('registers init action for register_block_patterns', function () {
        $this->setupHooks();

        Functions\when('add_filter')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerFilter($hook, $callback, $priority, $accepted_args);
            }
        );

        $setup = new Setup();
        $setup->init();

        $this->assertActionRegistered('init', [$setup, 'register_block_patterns']);
    });

    it('registers init action for register_block_styles', function () {
        $this->setupHooks();

        Functions\when('add_filter')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerFilter($hook, $callback, $priority, $accepted_args);
            }
        );

        $setup = new Setup();
        $setup->init();

        $this->assertActionRegistered('init', [$setup, 'register_block_styles']);
    });

    it('registers should_load_remote_block_patterns filter to return false', function () {
        $this->setupHooks();

        Functions\when('add_filter')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerFilter($hook, $callback, $priority, $accepted_args);
            }
        );

        $setup = new Setup();
        $setup->init();

        $this->assertFilterRegistered('should_load_remote_block_patterns', '__return_false');
    });
});

describe('Setup::setup_theme', function () {
    beforeEach(function () {
        Functions\when('add_theme_support')->justReturn(true);
        Functions\when('remove_theme_support')->justReturn(true);
        Functions\when('register_nav_menus')->justReturn(true);
        Functions\when('load_theme_textdomain')->justReturn(true);
        Functions\when('__')->returnArg(1);
    });

    it('sets global content_width to 720', function () {
        $setup = new Setup();
        $setup->setup_theme();

        expect($GLOBALS['content_width'])->toBe(720);
    });

    it('calls add_theme_support for automatic-feed-links', function () {
        $addedSupports = [];

        Functions\when('add_theme_support')->alias(
            static function (string $feature, mixed ...$args) use (&$addedSupports): void {
                $addedSupports[] = $feature;
            }
        );
        Functions\when('remove_theme_support')->justReturn(true);
        Functions\when('register_nav_menus')->justReturn(true);
        Functions\when('load_theme_textdomain')->justReturn(true);

        $setup = new Setup();
        $setup->setup_theme();

        expect($addedSupports)->toContain('automatic-feed-links');
        expect($addedSupports)->toContain('title-tag');
        expect($addedSupports)->toContain('post-thumbnails');
    });

    it('calls register_nav_menus with primary, footer, and social menus', function () {
        $captured = null;

        Functions\when('add_theme_support')->justReturn(true);
        Functions\when('remove_theme_support')->justReturn(true);
        Functions\when('load_theme_textdomain')->justReturn(true);
        Functions\when('register_nav_menus')->alias(
            static function (array $menus) use (&$captured): void {
                $captured = $menus;
            }
        );

        $setup = new Setup();
        $setup->setup_theme();

        expect($captured)->toHaveKey('primary');
        expect($captured)->toHaveKey('footer');
        expect($captured)->toHaveKey('social');
    });

    it('calls load_theme_textdomain with crispy-theme', function () {
        $capturedDomain = null;

        Functions\when('add_theme_support')->justReturn(true);
        Functions\when('remove_theme_support')->justReturn(true);
        Functions\when('register_nav_menus')->justReturn(true);
        Functions\when('load_theme_textdomain')->alias(
            static function (string $domain, string $path) use (&$capturedDomain): bool {
                $capturedDomain = $domain;
                return true;
            }
        );

        $setup = new Setup();
        $setup->setup_theme();

        expect($capturedDomain)->toBe('crispy-theme');
    });

    it('calls remove_theme_support for core-block-patterns', function () {
        $removedSupports = [];

        Functions\when('add_theme_support')->justReturn(true);
        Functions\when('remove_theme_support')->alias(
            static function (string $feature) use (&$removedSupports): void {
                $removedSupports[] = $feature;
            }
        );
        Functions\when('register_nav_menus')->justReturn(true);
        Functions\when('load_theme_textdomain')->justReturn(true);

        $setup = new Setup();
        $setup->setup_theme();

        expect($removedSupports)->toContain('core-block-patterns');
    });
});

describe('Setup::register_block_patterns', function () {
    it('registers crispytheme pattern category', function () {
        $capturedCategory = null;

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_pattern_category')->alias(
            static function (string $category, array $args) use (&$capturedCategory): void {
                $capturedCategory = $category;
            }
        );

        $setup = new Setup();
        $setup->register_block_patterns();

        expect($capturedCategory)->toBe('crispytheme');
    });
});

describe('Setup::register_block_styles', function () {
    it('registers outline style for core/button', function () {
        $registered = [];

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_style')->alias(
            static function (string $blockName, array $styleProperties) use (&$registered): void {
                $registered[] = ['block' => $blockName, 'name' => $styleProperties['name']];
            }
        );

        $setup = new Setup();
        $setup->register_block_styles();

        $match = array_filter($registered, fn($r) => $r['block'] === 'core/button' && $r['name'] === 'outline');
        expect(count($match))->toBe(1);
    });

    it('registers plain style for core/list', function () {
        $registered = [];

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_style')->alias(
            static function (string $blockName, array $styleProperties) use (&$registered): void {
                $registered[] = ['block' => $blockName, 'name' => $styleProperties['name']];
            }
        );

        $setup = new Setup();
        $setup->register_block_styles();

        $match = array_filter($registered, fn($r) => $r['block'] === 'core/list' && $r['name'] === 'plain');
        expect(count($match))->toBe(1);
    });

    it('registers shadow-box style for core/group', function () {
        $registered = [];

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_style')->alias(
            static function (string $blockName, array $styleProperties) use (&$registered): void {
                $registered[] = ['block' => $blockName, 'name' => $styleProperties['name']];
            }
        );

        $setup = new Setup();
        $setup->register_block_styles();

        $match = array_filter($registered, fn($r) => $r['block'] === 'core/group' && $r['name'] === 'shadow-box');
        expect(count($match))->toBe(1);
    });

    it('registers all three block styles', function () {
        $count = 0;

        Functions\when('__')->returnArg(1);
        Functions\when('register_block_style')->alias(
            static function (string $blockName, array $styleProperties) use (&$count): void {
                $count++;
            }
        );

        $setup = new Setup();
        $setup->register_block_styles();

        expect($count)->toBe(3);
    });
});
