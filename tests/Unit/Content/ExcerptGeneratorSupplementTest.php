<?php
/**
 * Excerpt Generator Supplementary Unit Tests
 *
 * Covers branches not reached by tests/Unit/ExcerptGeneratorTest.php:
 *  - init() registers filters
 *  - generate_excerpt: post_excerpt early-return
 *  - generate_excerpt: empty / non-string markdown → fallback
 *  - generate_excerpt: generates from markdown when meta present
 *  - get_excerpt_length: delegates to filter
 *  - get_excerpt_more: delegates to filter
 *  - generate_with_sentence_boundary: word-boundary fallback when no sentence
 *    end falls after min_length
 *  - generate_from_html: empty HTML → empty string
 *
 * Do NOT modify tests/Unit/ExcerptGeneratorTest.php.
 *
 * @package CrispyTheme\Tests\Unit\Content
 */

declare(strict_types=1);

use CrispyTheme\Content\ExcerptGenerator;
use CrispyTheme\Content\MarkdownRenderer;
use Brain\Monkey\Functions;

// WP_Post stub — guard prevents double-declaration when running the full suite.
// Uses untyped properties to remain compatible with MarkdownDropdownTest's stub,
// whichever file loads first in the Pest process.
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

// ---------------------------------------------------------------------------
// init() — hook registration
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::init', function () {
    beforeEach(function () {
        $this->setupHooks();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('registers get_the_excerpt filter', function () {
        $generator = new ExcerptGenerator();
        $generator->init();

        $filters = $this->getRegisteredFilters('get_the_excerpt');
        expect(count($filters))->toBeGreaterThan(0);
    });

    it('registers excerpt_length filter', function () {
        $generator = new ExcerptGenerator();
        $generator->init();

        $filters = $this->getRegisteredFilters('excerpt_length');
        expect(count($filters))->toBeGreaterThan(0);
    });

    it('registers excerpt_more filter', function () {
        $generator = new ExcerptGenerator();
        $generator->init();

        $filters = $this->getRegisteredFilters('excerpt_more');
        expect(count($filters))->toBeGreaterThan(0);
    });
});

// ---------------------------------------------------------------------------
// generate_excerpt — all branches
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::generate_excerpt', function () {
    beforeEach(function () {
        $this->mockPostMetaFunctions();
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns the existing excerpt when post already has a manual excerpt', function () {
        $post               = new WP_Post();
        $post->ID           = 1;
        $post->post_excerpt = 'Manual excerpt set by author.';

        $generator = new ExcerptGenerator();
        $result    = $generator->generate_excerpt('Manual excerpt set by author.', $post);
        expect($result)->toBe('Manual excerpt set by author.');
    });

    it('returns the original excerpt when no markdown meta is stored', function () {
        $post               = new WP_Post();
        $post->ID           = 2;
        $post->post_excerpt = '';

        // No meta set — get_post_meta returns empty string.
        $this->postMeta[2] = [MarkdownRenderer::META_KEY => ''];

        $generator = new ExcerptGenerator();
        $result    = $generator->generate_excerpt('Default excerpt.', $post);
        expect($result)->toBe('Default excerpt.');
    });

    it('returns the original excerpt when meta is non-string (e.g. false)', function () {
        $post               = new WP_Post();
        $post->ID           = 3;
        $post->post_excerpt = '';

        // get_post_meta returning false = non-string
        $this->postMeta[3] = [MarkdownRenderer::META_KEY => false];

        $generator = new ExcerptGenerator();
        $result    = $generator->generate_excerpt('Default.', $post);
        expect($result)->toBe('Default.');
    });

    it('generates an excerpt when markdown meta is present', function () {
        $post               = new WP_Post();
        $post->ID           = 4;
        $post->post_excerpt = '';

        $this->postMeta[4] = [MarkdownRenderer::META_KEY => '# Title\n\nThis is body text that should appear in the excerpt.'];

        $generator = new ExcerptGenerator();
        $result    = $generator->generate_excerpt('', $post);

        // Generated excerpt must be non-empty.
        expect($result)->toBeNonEmptyString();
    });
});

// ---------------------------------------------------------------------------
// get_excerpt_length
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::get_excerpt_length', function () {
    it('returns the default word count when filter is passthrough', function () {
        Functions\when('apply_filters')->returnArg(2);

        $generator = new ExcerptGenerator();
        $result    = $generator->get_excerpt_length(0);
        expect($result)->toBe(55);
    });

    it('returns custom value when filter overrides it', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            if ($filter === 'crispytheme_excerpt_length') {
                return 30;
            }
            return $value;
        });

        $generator = new ExcerptGenerator();
        $result    = $generator->get_excerpt_length(100);
        expect($result)->toBe(30);
    });
});

// ---------------------------------------------------------------------------
// get_excerpt_more
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::get_excerpt_more', function () {
    it('returns the default more text when filter is passthrough', function () {
        Functions\when('apply_filters')->returnArg(2);

        $generator = new ExcerptGenerator();
        $result    = $generator->get_excerpt_more('...');
        expect($result)->toBe('&hellip;');
    });

    it('returns custom more text when filter overrides it', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            if ($filter === 'crispytheme_excerpt_more') {
                return ' [read more]';
            }
            return $value;
        });

        $generator = new ExcerptGenerator();
        $result    = $generator->get_excerpt_more('...');
        expect($result)->toBe(' [read more]');
    });
});

// ---------------------------------------------------------------------------
// generate_from_html — edge cases and the more-text branch
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::generate_from_html — empty input', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('returns empty string for empty HTML', function () {
        $generator = new ExcerptGenerator();
        $result    = $generator->generate_from_html('');
        expect($result)->toBe('');
    });

    it('returns empty string for tags-only HTML', function () {
        $generator = new ExcerptGenerator();
        $result    = $generator->generate_from_html('<p></p>');
        expect($result)->toBe('');
    });
});

// Lines 127-128: the more-text branch inside generate_from_html.
// Triggered when str_word_count($text) > $word_count.
describe('ExcerptGenerator::generate_from_html — more text appended', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('appends the more text when content is truncated', function () {
        $generator = new ExcerptGenerator();

        // Build HTML with 20 words but pass word_count=5 to force truncation.
        $html   = '<p>' . implode(' ', array_fill(0, 20, 'word')) . '</p>';
        $result = $generator->generate_from_html($html, 5);

        expect($result)->toContain('&hellip;');
    });

    it('uses custom more text from filter', function () {
        Functions\when('apply_filters')->alias(function (string $filter, mixed $value): mixed {
            if ($filter === 'crispytheme_excerpt_more') {
                return ' [more]';
            }
            return $value;
        });

        $generator = new ExcerptGenerator();
        $html      = '<p>' . implode(' ', array_fill(0, 20, 'word')) . '</p>';
        $result    = $generator->generate_from_html($html, 5);

        expect($result)->toContain(' [more]');
    });
});

// ---------------------------------------------------------------------------
// generate_with_sentence_boundary — word-boundary fallback
// (text longer than min_length but no sentence-ending punctuation found
//  within max_length, so last_sentence == 0 < min_length → wp_trim_words)
// ---------------------------------------------------------------------------

describe('ExcerptGenerator::generate_with_sentence_boundary — word-boundary fallback', function () {
    beforeEach(function () {
        Functions\when('apply_filters')->returnArg(2);
    });

    it('falls back to wp_trim_words when no sentence boundary is found', function () {
        $generator = new ExcerptGenerator();

        // Text longer than min_length (100) but with no '. '/'! '/'? ' within max_length (200).
        // Use a long run of comma-separated words — no sentence-ending punctuation.
        $word      = 'word';
        $markdown  = implode(', ', array_fill(0, 60, $word)); // ~300 chars, no sentence end
        $result    = $generator->generate_with_sentence_boundary($markdown, 100, 200);

        // Should produce a non-empty result via the wp_trim_words fallback.
        expect($result)->toBeNonEmptyString();
    });

    it('returns full text when shorter than min_length', function () {
        $generator = new ExcerptGenerator();
        $markdown  = 'Short.';
        $result    = $generator->generate_with_sentence_boundary($markdown, 100, 200);
        expect($result)->toBe('Short.');
    });

    it('finds exclamation-mark sentence boundary', function () {
        $generator = new ExcerptGenerator();

        // Build text that has a '! ' inside the max_length window after min_length chars.
        $lead   = str_repeat('a', 110); // > min_length
        $rest   = ' Amazing! And more text here to fill up space after boundary.';
        $text   = $lead . $rest;
        $result = $generator->generate_with_sentence_boundary($text, 100, 200);

        // Result should end at '!' (last_sentence >= min_length branch).
        expect($result)->toEndWith('!');
    });

    it('finds question-mark sentence boundary', function () {
        $generator = new ExcerptGenerator();

        $lead   = str_repeat('b', 110);
        $rest   = ' Really? And then some more trailing text to fill space.';
        $text   = $lead . $rest;
        $result = $generator->generate_with_sentence_boundary($text, 100, 200);

        expect($result)->toEndWith('?');
    });
});
