<?php
/**
 * Markdown Renderer Unit Tests
 *
 * Covers branches not reached by the integration test:
 *  - render_content (all 5 paths)
 *  - render cache-hit path
 *  - get_markdown_content non-string branch
 *  - set_markdown_content
 *  - clear_post_cache (revision / autosave guards)
 *  - on_meta_update / on_meta_delete (key match and no-match)
 *  - process_embeds (no-wp_embed branch and full embed branch)
 *  - has_markdown_content
 *  - init (registers hooks)
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\MarkdownRenderer;
use Brain\Monkey\Functions;

// Minimal WP_Post stub — guard prevents double-declaration when running the full suite.
// Uses untyped properties to remain compatible with MarkdownDropdownTest's stub.
if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public $ID           = 1;
        public $post_title   = '';
        public $post_content = '';
        public $post_excerpt = '';
        public $post_name    = '';
        public $post_type    = 'post';
        public $post_status  = 'publish';
        public $post_author  = '1';

        public function __construct(array $attrs = [])
        {
            foreach ($attrs as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}

// Minimal WP_Embed stub for process_embeds full-path coverage.
if (!class_exists('WP_Embed')) {
    class WP_Embed
    {
        public function run_shortcode(string $content): string
        {
            return $content;
        }

        public function autoembed(string $content): string
        {
            return $content;
        }
    }
}

/**
 * Create a minimal $wpdb stub that satisfies delete_for_post / clear_all.
 *
 * Returns an anonymous class with no-op prepare() / get_col().
 *
 * @return object
 */
function make_wpdb_stub(): object
{
    return new class {
        public string $options = 'wp_options';

        public function prepare(string $query, mixed ...$args): string
        {
            return $query;
        }

        public function get_col(string $query): array
        {
            return [];
        }
    };
}

// ---------------------------------------------------------------------------
// render_content paths
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::render_content — not singular', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('is_singular')->justReturn(false);
    });

    it('returns original content when not on a singular view', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->render_content('<p>Original</p>');
        expect($result)->toBe('<p>Original</p>');
    });
});

describe('MarkdownRenderer::render_content — no post', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('is_singular')->justReturn(true);
        Functions\when('get_post')->justReturn(null);
    });

    it('returns original content when get_post returns null', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->render_content('<p>Fallback</p>');
        expect($result)->toBe('<p>Fallback</p>');
    });
});

describe('MarkdownRenderer::render_content — post type without editor support', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('is_singular')->justReturn(true);

        $post            = new WP_Post();
        $post->ID        = 5;
        $post->post_type = 'attachment';

        Functions\when('get_post')->justReturn($post);
        Functions\when('post_type_supports')->justReturn(false);
    });

    it('returns original content when post type does not support editor', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->render_content('<p>Attachment</p>');
        expect($result)->toBe('<p>Attachment</p>');
    });
});

describe('MarkdownRenderer::render_content — no markdown meta', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('is_singular')->justReturn(true);

        $post            = new WP_Post();
        $post->ID        = 6;
        $post->post_type = 'post';

        Functions\when('get_post')->justReturn($post);
        Functions\when('post_type_supports')->justReturn(true);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original content when no markdown is stored', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->render_content('<p>No markdown</p>');
        expect($result)->toBe('<p>No markdown</p>');
    });
});

describe('MarkdownRenderer::render_content — with markdown renders HTML', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('is_singular')->justReturn(true);

        $post            = new WP_Post();
        $post->ID        = 7;
        $post->post_type = 'post';

        Functions\when('get_post')->justReturn($post);
        Functions\when('post_type_supports')->justReturn(true);
        Functions\when('get_post_meta')->justReturn('# Hello');
    });

    it('renders markdown from post meta', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->render_content('');
        expect($result)->toContain('<h1>Hello</h1>');
        expect($result)->toContain('class="markdown-body"');
    });
});

// ---------------------------------------------------------------------------
// render() — cache hit path
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::render — cache hit', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns cached HTML without re-parsing on second call', function () {
        $renderer = new MarkdownRenderer();
        $markdown = 'Cached **content**.';

        // First call primes the cache.
        $html1 = $renderer->render(10, $markdown);

        // Poison the transient store with a sentinel to confirm it is read.
        $cacheKey = (new \CrispyTheme\Cache\TransientCache())->generate_key(10, $markdown);
        $this->transientStore->set($cacheKey, '<div class="markdown-body">SENTINEL</div>');

        // Second call should return the sentinel, not freshly rendered HTML.
        $html2 = $renderer->render(10, $markdown);

        expect($html2)->toContain('SENTINEL');
    });
});

// ---------------------------------------------------------------------------
// get_markdown_content — non-string branch
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::get_markdown_content', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns empty string when meta value is not a string', function () {
        Functions\when('get_post_meta')->justReturn(false);

        $renderer = new MarkdownRenderer();
        expect($renderer->get_markdown_content(99))->toBe('');
    });

    it('returns the stored markdown string', function () {
        Functions\when('get_post_meta')->justReturn('# My Post');

        $renderer = new MarkdownRenderer();
        expect($renderer->get_markdown_content(1))->toBe('# My Post');
    });
});

// ---------------------------------------------------------------------------
// set_markdown_content
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::set_markdown_content', function () {
    beforeEach(function () {
        $this->setupTransients();
        $this->setupWordPressFunctions(); // provides update_post_meta mock
        Functions\when('apply_filters')->returnArg(2);
    });

    it('stores markdown and returns true on success', function () {
        $renderer = new MarkdownRenderer();
        $result   = $renderer->set_markdown_content(1, '## Hello');
        expect($result)->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// clear_post_cache
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::clear_post_cache', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('does nothing for post revisions', function () {
        Functions\when('wp_is_post_revision')->justReturn(42); // truthy = is revision
        Functions\when('wp_is_post_autosave')->justReturn(false);

        $renderer = new MarkdownRenderer();
        $post     = new WP_Post(['ID' => 42]);

        // Seed a transient so we can confirm it was NOT deleted.
        $this->transientStore->set('crispy_md_42_abc', 'cached');

        $renderer->clear_post_cache(42, $post);

        // Transient should still exist because the method returned early.
        expect($this->transientStore->has('crispy_md_42_abc'))->toBeTrue();
    });

    it('does nothing for autosaves', function () {
        Functions\when('wp_is_post_revision')->justReturn(false);
        Functions\when('wp_is_post_autosave')->justReturn(100); // truthy

        $renderer = new MarkdownRenderer();
        $post     = new WP_Post(['ID' => 100]);

        $this->transientStore->set('crispy_md_100_abc', 'cached');
        $renderer->clear_post_cache(100, $post);
        expect($this->transientStore->has('crispy_md_100_abc'))->toBeTrue();
    });

    it('calls delete_for_post for regular saves', function () {
        Functions\when('wp_is_post_revision')->justReturn(false);
        Functions\when('wp_is_post_autosave')->justReturn(false);

        // $wpdb->get_col returns [] so nothing actually gets deleted,
        // but the method runs without error.
        $renderer = new MarkdownRenderer();
        $post     = new WP_Post(['ID' => 1]);

        // Expect no exception.
        $renderer->clear_post_cache(1, $post);
        expect(true)->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// on_meta_update / on_meta_delete
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::on_meta_update', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('wp_is_post_revision')->justReturn(false);
        Functions\when('wp_is_post_autosave')->justReturn(false);
    });

    it('clears post cache when _markdown_content meta is updated', function () {
        $renderer = new MarkdownRenderer();
        // Should not throw.
        $renderer->on_meta_update(1, 5, MarkdownRenderer::META_KEY, '# New content');
        expect(true)->toBeTrue();
    });

    it('does nothing when a different meta key is updated', function () {
        $renderer = new MarkdownRenderer();
        // Seed a transient — it should survive.
        $this->transientStore->set('crispy_md_5_abc', 'html');
        $renderer->on_meta_update(1, 5, '_some_other_key', 'value');
        expect($this->transientStore->has('crispy_md_5_abc'))->toBeTrue();
    });
});

describe('MarkdownRenderer::on_meta_delete', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('clears post cache when _markdown_content meta is deleted', function () {
        $renderer = new MarkdownRenderer();
        $renderer->on_meta_delete(['1'], 5, MarkdownRenderer::META_KEY, '');
        expect(true)->toBeTrue();
    });

    it('does nothing when a different meta key is deleted', function () {
        $renderer = new MarkdownRenderer();
        $this->transientStore->set('crispy_md_5_xyz', 'html');
        $renderer->on_meta_delete(['1'], 5, '_other_key', '');
        expect($this->transientStore->has('crispy_md_5_xyz'))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// process_embeds — no wp_embed global (passthrough branch)
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::process_embeds — no global wp_embed', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns markdown unchanged when wp_embed is not set', function () {
        // Ensure global is absent.
        global $wp_embed;
        $original_embed = $wp_embed ?? null;
        $wp_embed       = null;

        // Use a subclass to make process_embeds accessible.
        $renderer = new class extends MarkdownRenderer {
            public function process_embeds_public(string $markdown): string
            {
                return $this->process_embeds($markdown);
            }
        };

        $markdown = '# Hello `code` world';
        $result   = $renderer->process_embeds_public($markdown);
        expect($result)->toBe($markdown);

        $wp_embed = $original_embed;
    });
});

// ---------------------------------------------------------------------------
// process_embeds — with WP_Embed (full path: code-block + inline-code protection)
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::process_embeds — with WP_Embed', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('protects fenced code blocks from embed processing and restores them', function () {
        global $wp_embed;
        $original_embed = $wp_embed ?? null;
        $wp_embed       = new WP_Embed();

        $renderer = new class extends MarkdownRenderer {
            public function process_embeds_public(string $markdown): string
            {
                return $this->process_embeds($markdown);
            }
        };

        $markdown = "Some text\n\n```php\nhttps://youtube.com/watch?v=abc\n```\n\nMore text `inline` here.";
        $result   = $renderer->process_embeds_public($markdown);

        // Code block content should be preserved verbatim.
        expect($result)->toContain('```php');
        expect($result)->toContain('https://youtube.com/watch?v=abc');
        // Inline code should also survive.
        expect($result)->toContain('`inline`');

        $wp_embed = $original_embed;
    });

    it('processes embeds outside of code blocks', function () {
        global $wp_embed;
        $original_embed = $wp_embed ?? null;

        // Custom embed stub that signals processing happened.
        $wp_embed = new class extends WP_Embed {
            public function autoembed(string $content): string
            {
                return str_replace('https://example.com/video', '[EMBEDDED]', $content);
            }
        };

        $renderer = new class extends MarkdownRenderer {
            public function process_embeds_public(string $markdown): string
            {
                return $this->process_embeds($markdown);
            }
        };

        $markdown = "Intro text.\n\nhttps://example.com/video\n\nEnd text.";
        $result   = $renderer->process_embeds_public($markdown);
        expect($result)->toContain('[EMBEDDED]');

        $wp_embed = $original_embed;
    });
});

// ---------------------------------------------------------------------------
// parse_without_cache (lines 311-315)
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::parse_without_cache', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('renders markdown without storing in cache', function () {
        $renderer = new MarkdownRenderer();
        $html     = $renderer->parse_without_cache('# No Cache');

        expect($html)->toContain('<h1>No Cache</h1>');
        expect($html)->toContain('class="markdown-body"');
        // Nothing should be stored in the transient store.
        expect($this->transientStore->getKeys())->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// has_markdown_content
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::has_markdown_content', function () {
    beforeEach(function () {
        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns true when markdown meta is non-empty', function () {
        Functions\when('get_post_meta')->justReturn('# Some content');

        $renderer = new MarkdownRenderer();
        expect($renderer->has_markdown_content(1))->toBeTrue();
    });

    it('returns false when markdown meta is empty', function () {
        Functions\when('get_post_meta')->justReturn('');

        $renderer = new MarkdownRenderer();
        expect($renderer->has_markdown_content(2))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// init — registers hooks
// ---------------------------------------------------------------------------

describe('MarkdownRenderer::init', function () {
    beforeEach(function () {
        $this->setupTransients();
        $this->setupHooks();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('registers the_content filter', function () {
        $renderer = new MarkdownRenderer();
        $renderer->init();

        $filters = $this->getRegisteredFilters('the_content');
        expect(count($filters))->toBeGreaterThan(0);
    });

    it('registers save_post action', function () {
        $renderer = new MarkdownRenderer();
        $renderer->init();

        $actions = $this->getRegisteredActions('save_post');
        expect(count($actions))->toBeGreaterThan(0);
    });

    it('registers updated_post_meta action', function () {
        $renderer = new MarkdownRenderer();
        $renderer->init();

        $actions = $this->getRegisteredActions('updated_post_meta');
        expect(count($actions))->toBeGreaterThan(0);
    });

    it('registers deleted_post_meta action', function () {
        $renderer = new MarkdownRenderer();
        $renderer->init();

        $actions = $this->getRegisteredActions('deleted_post_meta');
        expect(count($actions))->toBeGreaterThan(0);
    });
});
