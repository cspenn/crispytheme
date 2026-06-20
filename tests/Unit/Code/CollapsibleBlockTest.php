<?php
/**
 * CollapsibleBlock Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\Code
 */

declare(strict_types=1);

use CrispyTheme\Code\CollapsibleBlock;
use Brain\Monkey\Functions;

describe('CollapsibleBlock::init', function () {
    beforeEach(function () {
        $this->setupHooks();
    });

    it('registers the register_block action on init', function () {
        $block = new CollapsibleBlock();
        $block->init();

        $actions = $this->getRegisteredActions('init');

        $found = false;
        foreach ($actions as $action) {
            if (
                is_array($action['callback'])
                && $action['callback'][0] === $block
                && $action['callback'][1] === 'register_block'
            ) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue('register_block should be hooked to init');
    });
});

describe('CollapsibleBlock::register_block', function () {
    it('calls register_block_type with the correct block name', function () {
        $registeredName = null;

        Functions\when('register_block_type')->alias(
            function (string $name, array $args) use (&$registeredName): void {
                $registeredName = $name;
            }
        );

        $block = new CollapsibleBlock();
        $block->register_block();

        expect($registeredName)->toBe('crispytheme/collapsible-code');
    });

    it('registers with api_version 3', function () {
        $registeredArgs = [];

        Functions\when('register_block_type')->alias(
            function (string $name, array $args) use (&$registeredArgs): void {
                $registeredArgs = $args;
            }
        );

        $block = new CollapsibleBlock();
        $block->register_block();

        expect($registeredArgs['api_version'])->toBe('3');
    });

    it('registers a render_callback', function () {
        $registeredArgs = [];

        Functions\when('register_block_type')->alias(
            function (string $name, array $args) use (&$registeredArgs): void {
                $registeredArgs = $args;
            }
        );

        $block = new CollapsibleBlock();
        $block->register_block();

        expect(array_key_exists('render_callback', $registeredArgs))->toBeTrue();
        expect($registeredArgs['render_callback'])->toBeCallable();
    });

    it('registers the expected attributes', function () {
        $registeredArgs = [];

        Functions\when('register_block_type')->alias(
            function (string $name, array $args) use (&$registeredArgs): void {
                $registeredArgs = $args;
            }
        );

        $block = new CollapsibleBlock();
        $block->register_block();

        $attrs = $registeredArgs['attributes'] ?? [];
        $expectedAttrKeys = ['title', 'code', 'language', 'startOpen', 'showLineNumbers', 'filename'];

        foreach ($expectedAttrKeys as $key) {
            expect(array_key_exists($key, $attrs))->toBeTrue(
                "Attribute '{$key}' should be registered"
            );
        }
    });
});

describe('CollapsibleBlock::render_block', function () {
    beforeEach(function () {
        $this->mockEscapeFunctions();
        $this->mockTranslationFunctions();

        Functions\when('get_block_wrapper_attributes')->alias(
            function (array $args = []): string {
                $classes = $args['class'] ?? '';
                return "class=\"{$classes}\"";
            }
        );
    });

    it('returns a string', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([]);

        expect($result)->toBeString();
    });

    it('renders a details element', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'title'    => 'Show Code',
            'code'     => 'echo "hello";',
            'language' => 'php',
        ]);

        expect($result)->toContain('<details');
        expect($result)->toContain('</details>');
    });

    it('renders a summary element containing the title', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'title' => 'My Code Block',
            'code'  => '',
        ]);

        expect($result)->toContain('<summary>');
        expect($result)->toContain('My Code Block');
    });

    it('renders the code in a pre/code block', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'code'     => 'print("hello")',
            'language' => 'python',
        ]);

        expect($result)->toContain('<pre');
        expect($result)->toContain('<code');
        expect($result)->toContain('print(&quot;hello&quot;)');
    });

    it('uses python as default language', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block(['code' => 'x = 1']);

        expect($result)->toContain('language-python');
    });

    it('applies the specified language class', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'code'     => 'echo "hi";',
            'language' => 'php',
        ]);

        expect($result)->toContain('language-php');
    });

    it('adds open attribute when startOpen is true', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'startOpen' => true,
        ]);

        expect($result)->toContain(' open');
    });

    it('does not add open attribute when startOpen is false', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'startOpen' => false,
        ]);

        // Should not contain bare " open" in the details tag.
        expect($result)->not->toContain('details  open');
    });

    it('adds line-numbers class when showLineNumbers is true', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'showLineNumbers' => true,
            'language'        => 'python',
        ]);

        expect($result)->toContain('line-numbers');
    });

    it('does not add line-numbers class when showLineNumbers is false', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'showLineNumbers' => false,
            'language'        => 'python',
        ]);

        expect($result)->not->toContain('line-numbers');
    });

    it('renders filename span when filename is provided', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'filename' => 'example.py',
        ]);

        expect($result)->toContain('collapsible-code-filename');
        expect($result)->toContain('example.py');
    });

    it('omits filename span when filename is empty', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'filename' => '',
        ]);

        expect($result)->not->toContain('collapsible-code-filename');
    });

    it('HTML-escapes the code content', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([
            'code' => '<script>alert("xss")</script>',
        ]);

        expect($result)->not->toContain('<script>');
        expect($result)->toContain('&lt;script&gt;');
    });

    it('uses default title Show Code when title attribute is missing', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([]);

        expect($result)->toContain('Show Code');
    });

    it('applies collapsible-code wrapper class', function () {
        $block  = new CollapsibleBlock();
        $result = $block->render_block([]);

        expect($result)->toContain('collapsible-code');
    });
});
