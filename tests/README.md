# CrispyTheme Testing Infrastructure

This directory contains a comprehensive testing utility for the CrispyTheme WordPress Block Theme.

## Directory Structure

```
tests/
├── Support/                 # Core infrastructure classes
│   ├── TestingUtility.php   # Main facade for all utilities
│   ├── TransientStore.php   # In-memory transient storage
│   ├── HookRegistry.php     # Track registered hooks
│   └── MarkdownAsserter.php # Markdown assertion engine
│
├── Concerns/                # Reusable traits
│   ├── MocksTransients.php
│   ├── MocksWordPressFunctions.php
│   ├── MocksWordPressHooks.php
│   ├── AssertsMarkdown.php
│   ├── AssertsCacheBehavior.php
│   └── AssertsHooks.php
│
├── Fixtures/                # Test data providers
│   ├── MarkdownFixtures.php
│   └── PostFixtures.php
│
├── Factories/               # Object factories
│   └── PostFactory.php
│
├── Architecture/            # Architecture tests
│   └── LayerDependencyTest.php
│
├── Unit/                    # Unit tests
├── Integration/             # Integration tests
├── Pest.php                 # Pest configuration
├── bootstrap.php            # Test bootstrap
└── TestCase.php             # Base test case
```

## Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Run architecture tests
vendor/bin/pest tests/Architecture

# Run with coverage
composer test:coverage

# Run specific test file
vendor/bin/pest tests/Unit/TransientCacheTest.php
```

## Using the Testing Infrastructure

### Quick Start

All tests automatically have access to the testing infrastructure through `TestCase.php` which includes all concern traits:

```php
<?php

declare(strict_types=1);

use CrispyTheme\MyClass;

describe('MyClass', function () {
    beforeEach(function () {
        // Setup transient mocking
        $this->setupTransients();

        // Setup hook mocking
        $this->setupHooks();
    });

    it('does something', function () {
        $result = (new MyClass())->doSomething();

        expect($result)->toBe('expected');
    });
});
```

### Mocking Transients

Use the `MocksTransients` trait methods:

```php
beforeEach(function () {
    $this->setupTransients();
});

it('uses transient cache', function () {
    // Transients work automatically
    set_transient('my_key', 'my_value');

    expect(get_transient('my_key'))->toBe('my_value');

    // Assert transient exists
    $this->assertTransientExists('my_key');

    // Assert transient value
    $this->assertTransientValue('my_key', 'my_value');
});
```

### Mocking WordPress Functions

Use the `MocksWordPressFunctions` trait:

```php
beforeEach(function () {
    $this->mockEscapeFunctions();      // esc_html, esc_attr, etc.
    $this->mockTranslationFunctions(); // __, _e, _n, etc.
    $this->mockSanitizeFunctions();    // sanitize_text_field, etc.
});

it('escapes output', function () {
    expect(esc_html('<script>'))->toBe('<script>');
});
```

### Mocking WordPress Hooks

Use the `MocksWordPressHooks` trait:

```php
beforeEach(function () {
    $this->setupHooks();
});

it('registers action', function () {
    add_action('init', 'my_callback');

    $this->assertActionRegistered('init', 'my_callback');
});

it('applies filter', function () {
    // Mock filter to return custom value
    $this->mockApplyFilters('my_filter', 'custom_value');

    expect(apply_filters('my_filter', 'original'))->toBe('custom_value');
});
```

### Markdown Assertions

Use the `AssertsMarkdown` trait for testing markdown rendering:

```php
it('renders heading', function () {
    $html = $renderer->render('# Hello');

    $this->assertMarkdownContainsHeading($html, 1, 'Hello');
    $this->assertMarkdownContainsHeadingLevel($html, 1);
});

it('renders code block', function () {
    $html = $renderer->render("```php\necho 'hi';\n```");

    $this->assertMarkdownContainsCodeBlock($html, 'php');
});

it('renders link', function () {
    $html = $renderer->render('[Link](https://example.com)');

    $this->assertMarkdownContainsLink($html, 'https://example.com', 'Link');
});
```

### Using Fixtures

Import and use predefined test data:

```php
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;
use CrispyTheme\Tests\Fixtures\PostFixtures;

it('renders complex markdown', function () {
    $markdown = MarkdownFixtures::complex();
    $html = $renderer->render(1, $markdown);

    expect($html)->toContain('<h1>');
});

it('handles post with markdown', function () {
    $postData = PostFixtures::withMarkdownContent();
    $post = PostFactory::create($postData)->build();

    expect($post->post_content)->toContain('markdown');
});
```

Available fixtures:
- `MarkdownFixtures::simple()` - Basic paragraph
- `MarkdownFixtures::withHeadings()` - Various heading levels
- `MarkdownFixtures::withCodeBlocks()` - Code blocks with languages
- `MarkdownFixtures::withTables()` - Markdown tables
- `MarkdownFixtures::withLists()` - Ordered and unordered lists
- `MarkdownFixtures::complex()` - All features combined
- `MarkdownFixtures::edgeCases()` - Edge cases and special characters
- `MarkdownFixtures::withWordCount(int $count)` - Specific word count

### Using Factories

Create mock WordPress objects with fluent API:

```php
use CrispyTheme\Tests\Factories\PostFactory;

it('processes post', function () {
    $post = PostFactory::create()
        ->withId(42)
        ->withTitle('My Post')
        ->withContent('Post content')
        ->withStatus('publish')
        ->withMeta('_markdown_content', '# Hello')
        ->build();

    expect($post->ID)->toBe(42);
    expect($post->post_title)->toBe('My Post');
});
```

### Cache Behavior Assertions

Use `AssertsCacheBehavior` for testing cache operations:

```php
it('caches rendered content', function () {
    $this->setupTransients();

    // First render - cache miss
    $renderer->render(1, 'content');
    $this->assertCacheMiss();

    // Second render - cache hit
    $renderer->render(1, 'content');
    $this->assertCacheHit();
});
```

## Architecture Testing

Architecture tests validate layer boundaries defined in `deptrac.yaml`:

```php
test('Parser layer has no internal dependencies')
    ->expect('CrispyTheme\Parser')
    ->not->toUse([
        'CrispyTheme\Theme',
        'CrispyTheme\Admin',
        'CrispyTheme\Content',
    ]);
```

Run architecture tests:
```bash
vendor/bin/pest tests/Architecture
```

## Custom Expectations

The testing infrastructure provides custom Pest expectations:

```php
// Check if string is non-empty
expect($string)->toBeNonEmptyString();

// Check if HTML contains specific CSS class
expect($html)->toHaveHtmlClass('my-class');

// Check if HTML contains element with text
expect($html)->toHaveHtmlElement('h1', 'Hello World');

// Check if HTML is valid
expect($html)->toBeValidHtml();

// Check if value is WP_Post
expect($post)->toBeWpPost();
```

## Test Groups

Tests are organized into groups:

- `unit` - Unit tests (fast, isolated)
- `integration` - Integration tests (may use more dependencies)
- `architecture` - Architecture validation tests

Run specific groups:
```bash
vendor/bin/pest --group=unit
vendor/bin/pest --group=integration
```

## Writing New Tests

1. Create test file in appropriate directory (`Unit/` or `Integration/`)
2. Use `describe()` blocks to group related tests
3. Use `beforeEach()` to setup mocking
4. Use trait methods for common operations
5. Use fixtures for test data
6. Use factories for mock objects

Example:
```php
<?php

declare(strict_types=1);

use CrispyTheme\MyClass;
use CrispyTheme\Tests\Fixtures\MarkdownFixtures;

describe('MyClass', function () {
    beforeEach(function () {
        $this->setupTransients();
        $this->mockApplyFiltersPassthrough();

        $this->instance = new MyClass();
    });

    it('processes markdown correctly', function () {
        $markdown = MarkdownFixtures::simple();
        $result = $this->instance->process($markdown);

        expect($result)->toBeNonEmptyString();
        $this->assertMarkdownStripped($result);
    });

    it('caches results', function () {
        $this->instance->process('content');

        $this->assertTransientExists('my_cache_key');
    });
});
```

## Troubleshooting

### Tests failing with "Function not defined"
Ensure you've set up the appropriate mocks in `beforeEach()`:
```php
$this->mockEscapeFunctions();
$this->mockTranslationFunctions();
```

### Transient assertions failing
Make sure `$this->setupTransients()` is called before your test code.

### Hook assertions not working
Ensure `$this->setupHooks()` is called and you're using the trait's mocked versions.
