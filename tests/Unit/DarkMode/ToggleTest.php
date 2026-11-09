<?php
/**
 * DarkMode Toggle Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\DarkMode
 */

declare(strict_types=1);

use CrispyTheme\DarkMode\Toggle;
use Brain\Monkey\Functions;

describe('Toggle constants', function () {
    it('has correct STORAGE_KEY constant', function () {
        expect(Toggle::STORAGE_KEY)->toBe('crispy-dark-mode');
    });

    it('has correct MODE_AUTO constant', function () {
        expect(Toggle::MODE_AUTO)->toBe('auto');
    });

    it('has correct MODE_LIGHT constant', function () {
        expect(Toggle::MODE_LIGHT)->toBe('light');
    });

    it('has correct MODE_DARK constant', function () {
        expect(Toggle::MODE_DARK)->toBe('dark');
    });
});

describe('Toggle::init', function () {
    it('registers body_class filter for add_body_class', function () {
        $this->setupHooks();

        $toggle = new Toggle();
        $toggle->init();

        $this->assertFilterRegistered('body_class', [$toggle, 'add_body_class']);
    });

    it('registers init action for register_block', function () {
        $this->setupHooks();

        $toggle = new Toggle();
        $toggle->init();

        $this->assertActionRegistered('init', [$toggle, 'register_block']);
    });
});

describe('Toggle::add_body_class', function () {
    it('adds crispy-theme-dark-mode-enabled to body classes', function () {
        $toggle = new Toggle();
        $result = $toggle->add_body_class(['some-class']);

        expect($result)->toContain('crispy-theme-dark-mode-enabled');
    });

    it('preserves existing body classes', function () {
        $toggle  = new Toggle();
        $initial = ['page-template-default', 'single'];
        $result  = $toggle->add_body_class($initial);

        expect($result)->toContain('page-template-default');
        expect($result)->toContain('single');
        expect($result)->toContain('crispy-theme-dark-mode-enabled');
    });

    it('works with empty classes array', function () {
        $toggle = new Toggle();
        $result = $toggle->add_body_class([]);

        expect($result)->toBe(['crispy-theme-dark-mode-enabled']);
    });
});

describe('Toggle::register_block', function () {
    it('registers the dark-mode-toggle block type', function () {
        $capturedBlockName = null;

        Functions\when('register_block_type')->alias(
            static function (string $blockName, array $args) use (&$capturedBlockName): void {
                $capturedBlockName = $blockName;
            }
        );

        $toggle = new Toggle();
        $toggle->register_block();

        expect($capturedBlockName)->toBe('crispytheme/dark-mode-toggle');
    });

    it('registers block with a render_callback', function () {
        $capturedArgs = null;

        Functions\when('register_block_type')->alias(
            static function (string $blockName, array $args) use (&$capturedArgs): void {
                $capturedArgs = $args;
            }
        );

        $toggle = new Toggle();
        $toggle->register_block();

        expect($capturedArgs)->toHaveKey('render_callback');
        expect(is_callable($capturedArgs['render_callback']))->toBeTrue();
    });

    it('registers block with showLabel and className attributes', function () {
        $capturedArgs = null;

        Functions\when('register_block_type')->alias(
            static function (string $blockName, array $args) use (&$capturedArgs): void {
                $capturedArgs = $args;
            }
        );

        $toggle = new Toggle();
        $toggle->register_block();

        expect($capturedArgs)->toHaveKey('attributes');
        expect($capturedArgs['attributes'])->toHaveKey('showLabel');
        expect($capturedArgs['attributes'])->toHaveKey('className');
    });
});

describe('Toggle::render_toggle with default attributes', function () {
    beforeEach(function () {
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('esc_html_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
    });

    it('renders a button element', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('<button');
        expect($output)->toContain('</button>');
    });

    it('includes the base toggle CSS class', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('crispy-dark-mode-toggle');
    });

    it('includes aria-label attribute', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('aria-label=');
        expect($output)->toContain('Toggle dark mode');
    });

    it('includes aria-pressed attribute set to false', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('aria-pressed="false"');
    });

    it('includes data-dark-mode-toggle attribute', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('data-dark-mode-toggle');
    });

    it('includes sun icon span', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('crispy-dark-mode-toggle__icon--light');
    });

    it('includes moon icon span', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->toContain('crispy-dark-mode-toggle__icon--dark');
    });

    it('includes sun icon SVG content', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        // Sun icon has a circle element.
        expect($output)->toContain('<circle');
    });

    it('includes moon icon SVG path', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        // Moon icon has a path element.
        expect($output)->toContain('<path');
    });

    it('does not render label spans when showLabel is false', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['showLabel' => false]);

        expect($output)->not->toContain('crispy-dark-mode-toggle__label');
    });

    it('does not render label spans with empty attributes', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([]);

        expect($output)->not->toContain('crispy-dark-mode-toggle__label');
    });
});

describe('Toggle::render_toggle with showLabel enabled', function () {
    beforeEach(function () {
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('esc_html_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
    });

    it('renders light label span when showLabel is true', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['showLabel' => true]);

        expect($output)->toContain('crispy-dark-mode-toggle__label--light');
    });

    it('renders dark label span when showLabel is true', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['showLabel' => true]);

        expect($output)->toContain('crispy-dark-mode-toggle__label--dark');
    });

    it('renders Light and Dark text when showLabel is true', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['showLabel' => true]);

        expect($output)->toContain('Light');
        expect($output)->toContain('Dark');
    });
});

describe('Toggle::render_toggle with custom className', function () {
    beforeEach(function () {
        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
        Functions\when('esc_attr_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
        Functions\when('esc_html_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        );
    });

    it('appends custom className to button classes', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['className' => 'my-custom-class']);

        expect($output)->toContain('my-custom-class');
        expect($output)->toContain('crispy-dark-mode-toggle');
    });

    it('does not add extra class when className is empty string', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle(['className' => '']);

        // class attribute should only contain the base class.
        expect($output)->toContain('class="crispy-dark-mode-toggle"');
    });

    it('renders both showLabel and custom className together', function () {
        $toggle = new Toggle();
        $output = $toggle->render_toggle([
            'showLabel' => true,
            'className' => 'header-toggle',
        ]);

        expect($output)->toContain('header-toggle');
        expect($output)->toContain('crispy-dark-mode-toggle__label--light');
        expect($output)->toContain('crispy-dark-mode-toggle__label--dark');
    });
});

describe('Toggle::get_toggle_styles', function () {
    it('returns a non-empty string', function () {
        $css = Toggle::get_toggle_styles();
        expect($css)->toBeString();
        expect(strlen($css))->toBeGreaterThan(0);
    });

    it('contains the toggle selector', function () {
        $css = Toggle::get_toggle_styles();
        expect($css)->toContain('.crispy-dark-mode-toggle');
    });

    it('contains dark-mode overrides', function () {
        $css = Toggle::get_toggle_styles();
        expect($css)->toContain('.dark-mode');
    });

    it('returns CSS as a static method', function () {
        // Verify it can be called without an instance.
        $css = Toggle::get_toggle_styles();
        expect($css)->toBeString();
    });
});
