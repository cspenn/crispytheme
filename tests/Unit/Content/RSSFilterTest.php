<?php
/**
 * RSS Filter Unit Tests
 *
 * Covers all public methods of RSSFilter:
 *  - init()
 *  - filter_feed_content(): no-post / empty-markdown / excerpt-mode / full-mode
 *  - filter_feed_excerpt(): no-post / has-post_excerpt / empty-markdown / generates
 *  - get_content_mode(): default / filter-overridden / invalid → full
 *  - set_content_mode(): invalid → false / valid → true
 *  - get_available_modes(): static, returns two entries
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\RSSFilter;
use CrispyTheme\Content\MarkdownRenderer;
use Brain\Monkey\Functions;

// WP_Post stub — guard prevents double-declaration when running the full suite.
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

/**
 * Create a minimal $wpdb stub for tests that trigger delete_for_post.
 *
 * @return object
 */
function make_rss_wpdb_stub(): object
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
// init() — hook registration
// ---------------------------------------------------------------------------

describe('RSSFilter::init', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        $this->setupHooks();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('registers the_content_feed filter', function () {
        $filter = new RSSFilter();
        $filter->init();

        $filters = $this->getRegisteredFilters('the_content_feed');
        expect(count($filters))->toBeGreaterThan(0);
    });

    it('registers the_excerpt_rss filter', function () {
        $filter = new RSSFilter();
        $filter->init();

        $filters = $this->getRegisteredFilters('the_excerpt_rss');
        expect(count($filters))->toBeGreaterThan(0);
    });
});

// ---------------------------------------------------------------------------
// filter_feed_content
// ---------------------------------------------------------------------------

describe('RSSFilter::filter_feed_content — no post', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post')->justReturn(null);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original content when get_post is null', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_content('<p>Original</p>', 'rss2');
        expect($result)->toBe('<p>Original</p>');
    });
});

describe('RSSFilter::filter_feed_content — no markdown', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);

        $post     = new WP_Post();
        $post->ID = 1;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original content when no markdown meta is found', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_content('<p>RSS content</p>', 'rss2');
        expect($result)->toBe('<p>RSS content</p>');
    });
});

describe('RSSFilter::filter_feed_content — excerpt mode', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();

        $post     = new WP_Post();
        $post->ID = 2;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('This is **markdown** content with enough words to generate an excerpt here.');

        // get_option returns 'excerpt' mode; apply_filters passes through
        Functions\when('get_option')->justReturn(RSSFilter::MODE_EXCERPT);
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns generated excerpt in excerpt mode', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_content('', 'rss2');

        expect($result)->toBeString();
        expect($result)->not->toContain('<strong>'); // HTML tags stripped in excerpt
    });
});

describe('RSSFilter::filter_feed_content — full content mode', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();

        $post     = new WP_Post();
        $post->ID = 3;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('# Full Content');

        Functions\when('get_option')->justReturn(RSSFilter::MODE_FULL);
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns rendered HTML in full content mode', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_content('', 'rss2');

        expect($result)->toContain('<h1>Full Content</h1>');
        expect($result)->toContain('class="markdown-body"');
    });
});

// ---------------------------------------------------------------------------
// filter_feed_excerpt
// ---------------------------------------------------------------------------

describe('RSSFilter::filter_feed_excerpt — no post', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post')->justReturn(null);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original excerpt when get_post is null', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_excerpt('Existing excerpt.');
        expect($result)->toBe('Existing excerpt.');
    });
});

describe('RSSFilter::filter_feed_excerpt — has manual excerpt', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);

        $post               = new WP_Post();
        $post->ID           = 4;
        $post->post_excerpt = 'Manually set excerpt.';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original excerpt unchanged when post has manual excerpt', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_excerpt('Manually set excerpt.');
        expect($result)->toBe('Manually set excerpt.');
    });
});

describe('RSSFilter::filter_feed_excerpt — no markdown', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);

        $post               = new WP_Post();
        $post->ID           = 5;
        $post->post_excerpt = '';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn('');
    });

    it('returns original excerpt when no markdown meta is stored', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_excerpt('Default excerpt.');
        expect($result)->toBe('Default excerpt.');
    });
});

describe('RSSFilter::filter_feed_excerpt — generates from markdown', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();

        $this->setupTransients();
        Functions\when('apply_filters')->returnArg(2);

        $post               = new WP_Post();
        $post->ID           = 6;
        $post->post_excerpt = '';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_meta')->justReturn(
            'This is body text with enough words for an auto-generated excerpt from markdown.'
        );
    });

    it('generates an excerpt from markdown content', function () {
        $filter = new RSSFilter();
        $result = $filter->filter_feed_excerpt('');
        expect($result)->toBeNonEmptyString();
        expect($result)->toContain('body text');
    });
});

// ---------------------------------------------------------------------------
// get_content_mode
// ---------------------------------------------------------------------------

describe('RSSFilter::get_content_mode', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();
    });

    it('returns full by default when no option is set', function () {
        Functions\when('get_option')->justReturn(RSSFilter::MODE_FULL);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post_meta')->justReturn('');

        $filter = new RSSFilter();
        expect($filter->get_content_mode())->toBe(RSSFilter::MODE_FULL);
    });

    it('returns excerpt when option is set to excerpt', function () {
        Functions\when('get_option')->justReturn(RSSFilter::MODE_EXCERPT);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('get_post_meta')->justReturn('');

        $filter = new RSSFilter();
        expect($filter->get_content_mode())->toBe(RSSFilter::MODE_EXCERPT);
    });

    it('returns full when filter sets an invalid value', function () {
        Functions\when('get_option')->justReturn(RSSFilter::MODE_FULL);
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            if ($filter === 'crispytheme_rss_content_mode') {
                return 'invalid_value';
            }
            return $value;
        });
        Functions\when('get_post_meta')->justReturn('');

        $filter = new RSSFilter();
        expect($filter->get_content_mode())->toBe(RSSFilter::MODE_FULL);
    });

    it('uses the value returned by the crispytheme_rss_content_mode filter', function () {
        Functions\when('get_option')->justReturn(RSSFilter::MODE_FULL);
        Functions\when('apply_filters')->alias(function (string $hook, mixed $value): mixed {
            if ($hook === 'crispytheme_rss_content_mode') {
                return RSSFilter::MODE_EXCERPT;
            }
            return $value;
        });
        Functions\when('get_post_meta')->justReturn('');

        $filter = new RSSFilter();
        expect($filter->get_content_mode())->toBe(RSSFilter::MODE_EXCERPT);
    });
});

// ---------------------------------------------------------------------------
// set_content_mode
// ---------------------------------------------------------------------------

describe('RSSFilter::set_content_mode', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();
    });

    it('returns false for an invalid mode', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('apply_filters')->returnArg(2);

        $filter = new RSSFilter();
        expect($filter->set_content_mode('unknown'))->toBeFalse();
    });

    it('calls update_option and returns true for a valid mode', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('update_option')->justReturn(true);

        $filter = new RSSFilter();
        expect($filter->set_content_mode(RSSFilter::MODE_FULL))->toBeTrue();
    });

    it('calls update_option and returns true for excerpt mode', function () {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('update_option')->justReturn(true);

        $filter = new RSSFilter();
        expect($filter->set_content_mode(RSSFilter::MODE_EXCERPT))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// get_available_modes (static)
// ---------------------------------------------------------------------------

describe('RSSFilter::get_available_modes', function () {
    beforeEach(function () {
        global $wpdb;
        $wpdb = make_rss_wpdb_stub();
    });

    it('returns an array with full and excerpt modes', function () {
        // __ is stubbed in bootstrap.
        $modes = RSSFilter::get_available_modes();

        expect($modes)->toBeArray();
        expect($modes)->toHaveKey(RSSFilter::MODE_FULL);
        expect($modes)->toHaveKey(RSSFilter::MODE_EXCERPT);
        expect(count($modes))->toBe(2);
    });
});
