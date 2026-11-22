<?php
/**
 * OptionsPage Unit Tests
 *
 * Tests Admin\OptionsPage: sanitize_*, render_*_field, register_settings,
 * enqueue_admin_scripts, and register_simple_field (via register_settings).
 *
 * @package CrispyTheme\Tests\Unit\Admin
 */

declare(strict_types=1);

use CrispyTheme\Admin\OptionsPage;
use CrispyTheme\Content\RSSFilter;
use Brain\Monkey\Functions;

// WordPress constant required by MarkdownDropdown::get_top_posts() -> $wpdb->get_results().
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// ---------------------------------------------------------------------------
// Shared helper: set up all WP functions commonly needed by render methods.
// ---------------------------------------------------------------------------

function stubRenderFunctions(): void
{
    Functions\when('esc_html')->alias(
        static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
    Functions\when('esc_attr')->alias(
        static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
    Functions\when('esc_url')->alias(
        static fn(string $url): string => filter_var($url, FILTER_SANITIZE_URL) ?: $url
    );
    Functions\when('esc_textarea')->alias(
        static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
    );
    Functions\when('esc_js')->alias(
        static fn(string $text): string => addslashes($text)
    );
    Functions\when('esc_attr_e')->alias(
        static function (string $text, string $domain = 'default'): void {
            echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    );
    Functions\when('esc_html__')->alias(
        static fn(string $text, string $domain = 'default'): string => $text
    );
    Functions\when('esc_html_e')->alias(
        static function (string $text, string $domain = 'default'): void {
            echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    );
    Functions\when('__')->alias(
        static fn(string $text, string $domain = 'default'): string => $text
    );
    Functions\when('_e')->alias(
        static function (string $text, string $domain = 'default'): void {
            echo $text;
        }
    );
    Functions\when('checked')->alias(
        static function (mixed $checked, mixed $current = true, bool $echo = true): string {
            $result = $checked == $current ? ' checked="checked"' : '';
            if ($echo) {
                echo $result;
            }
            return $result;
        }
    );
    Functions\when('selected')->alias(
        static function (mixed $selected, mixed $current = true, bool $echo = true): string {
            $result = $selected == $current ? ' selected="selected"' : '';
            if ($echo) {
                echo $result;
            }
            return $result;
        }
    );
    Functions\when('get_option')->alias(
        static fn(string $key, mixed $default = false): mixed => $default
    );
    // Note: number_format and sprintf are PHP native functions — do NOT mock them.
}

describe('OptionsPage', function () {

    // ------------------------------------------------------------------
    // Static accessor
    // ------------------------------------------------------------------

    describe('get_page_slug()', function () {
        it('returns the correct page slug', function () {
            expect(OptionsPage::get_page_slug())->toBe('crispytheme-options');
        });
    });

    // ------------------------------------------------------------------
    // init()
    // ------------------------------------------------------------------

    describe('init()', function () {
        it('registers admin_menu, admin_init, and admin_enqueue_scripts actions', function () {
            $registered = [];
            Functions\when('add_action')->alias(
                function (string $hook, mixed $callback) use (&$registered): bool {
                    $registered[] = $hook;
                    return true;
                }
            );

            (new OptionsPage())->init();

            expect($registered)->toContain('admin_menu');
            expect($registered)->toContain('admin_init');
            expect($registered)->toContain('admin_enqueue_scripts');
        });
    });

    // ------------------------------------------------------------------
    // add_options_page()
    // ------------------------------------------------------------------

    describe('add_options_page()', function () {
        it('calls add_theme_page with the correct slug', function () {
            Functions\when('__')->alias(
                static fn(string $text, string $domain = 'default'): string => $text
            );
            $slug = null;
            Functions\when('add_theme_page')->alias(
                function (string $pageTitle, string $menuTitle, string $cap, string $menuSlug) use (&$slug): void {
                    $slug = $menuSlug;
                }
            );

            (new OptionsPage())->add_options_page();

            expect($slug)->toBe('crispytheme-options');
        });
    });

    // ------------------------------------------------------------------
    // enqueue_admin_scripts()
    // ------------------------------------------------------------------

    describe('enqueue_admin_scripts()', function () {
        beforeEach(function () {
            Functions\when('esc_js')->alias(static fn(string $t): string => addslashes($t));
            Functions\when('__')->alias(
                static fn(string $t, string $d = 'default'): string => $t
            );
        });

        it('returns early for a different admin hook suffix', function () {
            $mediaEnqueued = false;
            Functions\when('wp_enqueue_media')->alias(function () use (&$mediaEnqueued): void {
                $mediaEnqueued = true;
            });

            (new OptionsPage())->enqueue_admin_scripts('toplevel_page_something_else');

            // wp_enqueue_media must not be called when hook suffix doesn't match.
            expect($mediaEnqueued)->toBeFalse();
        });

        it('enqueues scripts on the correct appearance page hook', function () {
            Functions\when('wp_enqueue_media')->justReturn(null);
            Functions\when('wp_register_script')->justReturn(true);
            Functions\when('wp_enqueue_script')->justReturn(null);
            Functions\when('wp_add_inline_script')->justReturn(true);

            $enqueuedMedia = false;
            Functions\when('wp_enqueue_media')->alias(function () use (&$enqueuedMedia): void {
                $enqueuedMedia = true;
            });

            (new OptionsPage())->enqueue_admin_scripts('appearance_page_crispytheme-options');

            expect($enqueuedMedia)->toBeTrue();
        });

        it('registers script with jquery and media-editor dependencies', function () {
            Functions\when('wp_enqueue_media')->justReturn(null);
            Functions\when('wp_enqueue_script')->justReturn(null);
            Functions\when('wp_add_inline_script')->justReturn(true);

            $deps = null;
            Functions\when('wp_register_script')->alias(
                function (string $handle, mixed $src, array $dependencies) use (&$deps): bool {
                    $deps = $dependencies;
                    return true;
                }
            );

            (new OptionsPage())->enqueue_admin_scripts('appearance_page_crispytheme-options');

            expect($deps)->toContain('jquery');
            expect($deps)->toContain('media-editor');
        });

        it('adds inline script with jQuery media uploader code', function () {
            Functions\when('wp_enqueue_media')->justReturn(null);
            Functions\when('wp_register_script')->justReturn(true);
            Functions\when('wp_enqueue_script')->justReturn(null);

            $inlineScript = null;
            Functions\when('wp_add_inline_script')->alias(
                function (string $handle, string $data) use (&$inlineScript): bool {
                    $inlineScript = $data;
                    return true;
                }
            );

            (new OptionsPage())->enqueue_admin_scripts('appearance_page_crispytheme-options');

            expect($inlineScript)->toContain('crispytheme-media-upload');
            expect($inlineScript)->toContain('wp.media');
        });
    });

    // ------------------------------------------------------------------
    // register_settings()
    // ------------------------------------------------------------------

    // Shared setup for register_settings tests: stub WP functions, let PHP
    // native sprintf/number_format run naturally.
    function stubSettingsFunctions(): void
    {
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(true);
    }

    describe('register_settings()', function () {
        it('calls add_settings_section at least 8 times for all sections', function () {
            stubSettingsFunctions();

            $sectionCount = 0;
            Functions\when('add_settings_section')->alias(
                function () use (&$sectionCount): void {
                    $sectionCount++;
                }
            );
            Functions\when('register_setting')->justReturn(true);

            (new OptionsPage())->register_settings();

            // rss, archive, markdown, layout, homepage, conversion, social_proof, schema, analytics, shortcodes = 10
            expect($sectionCount)->toBeGreaterThanOrEqual(8);
        });

        it('registers setting for RSS content option key', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            expect($registeredOptions)->toContain(RSSFilter::OPTION_KEY);
        });

        it('registers setting for archive display, parser type, sidebar and max width', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            expect($registeredOptions)->toContain('crispytheme_archive_display');
            expect($registeredOptions)->toContain('crispytheme_parser_type');
            expect($registeredOptions)->toContain('crispytheme_sidebar_width');
            expect($registeredOptions)->toContain('crispytheme_max_width');
        });

        it('registers 6 client logo settings in a loop', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            for ($i = 1; $i <= 6; $i++) {
                expect($registeredOptions)->toContain("crispytheme_client_logo_{$i}");
            }
        });

        it('registers social proof field options', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            expect($registeredOptions)->toContain('crispytheme_posteventscore_value');
            expect($registeredOptions)->toContain('crispytheme_testimonial_quote');
        });

        it('registers schema field options', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            expect($registeredOptions)->toContain('crispytheme_schema_person_name');
            expect($registeredOptions)->toContain('crispytheme_schema_org_name');
            expect($registeredOptions)->toContain('crispytheme_schema_video_url');
        });

        it('registers markdown shortcodes setting', function () {
            stubSettingsFunctions();

            $registeredOptions = [];
            Functions\when('register_setting')->alias(
                function (string $group, string $option) use (&$registeredOptions): bool {
                    $registeredOptions[] = $option;
                    return true;
                }
            );

            (new OptionsPage())->register_settings();

            expect($registeredOptions)->toContain('crispytheme_markdown_shortcodes');
        });
    });

    // ------------------------------------------------------------------
    // Sanitize methods
    // ------------------------------------------------------------------

    describe('sanitize_rss_mode()', function () {
        it('returns "full" for the valid full mode', function () {
            $page = new OptionsPage();
            expect($page->sanitize_rss_mode(RSSFilter::MODE_FULL))->toBe(RSSFilter::MODE_FULL);
        });

        it('returns "excerpt" for the valid excerpt mode', function () {
            $page = new OptionsPage();
            expect($page->sanitize_rss_mode(RSSFilter::MODE_EXCERPT))->toBe(RSSFilter::MODE_EXCERPT);
        });

        it('returns default "full" for invalid value', function () {
            $page = new OptionsPage();
            expect($page->sanitize_rss_mode('invalid_mode'))->toBe(RSSFilter::MODE_FULL);
        });

        it('returns default "full" for null input', function () {
            $page = new OptionsPage();
            expect($page->sanitize_rss_mode(null))->toBe(RSSFilter::MODE_FULL);
        });
    });

    describe('sanitize_archive_display()', function () {
        it('accepts "excerpt"', function () {
            expect((new OptionsPage())->sanitize_archive_display('excerpt'))->toBe('excerpt');
        });

        it('accepts "full"', function () {
            expect((new OptionsPage())->sanitize_archive_display('full'))->toBe('full');
        });

        it('returns default "excerpt" for invalid value', function () {
            expect((new OptionsPage())->sanitize_archive_display('bad'))->toBe('excerpt');
        });
    });

    describe('sanitize_parser_type()', function () {
        it('accepts "extra"', function () {
            expect((new OptionsPage())->sanitize_parser_type('extra'))->toBe('extra');
        });

        it('accepts "basic"', function () {
            expect((new OptionsPage())->sanitize_parser_type('basic'))->toBe('basic');
        });

        it('returns default "extra" for invalid value', function () {
            expect((new OptionsPage())->sanitize_parser_type('unknown'))->toBe('extra');
        });
    });

    describe('sanitize_sidebar_width()', function () {
        it('accepts "300px"', function () {
            expect((new OptionsPage())->sanitize_sidebar_width('300px'))->toBe('300px');
        });

        it('accepts "350px"', function () {
            expect((new OptionsPage())->sanitize_sidebar_width('350px'))->toBe('350px');
        });

        it('accepts "400px"', function () {
            expect((new OptionsPage())->sanitize_sidebar_width('400px'))->toBe('400px');
        });

        it('returns default "350px" for invalid value', function () {
            expect((new OptionsPage())->sanitize_sidebar_width('500px'))->toBe('350px');
        });
    });

    describe('sanitize_max_width()', function () {
        it('accepts "1400px"', function () {
            expect((new OptionsPage())->sanitize_max_width('1400px'))->toBe('1400px');
        });

        it('accepts "1600px"', function () {
            expect((new OptionsPage())->sanitize_max_width('1600px'))->toBe('1600px');
        });

        it('accepts "1800px"', function () {
            expect((new OptionsPage())->sanitize_max_width('1800px'))->toBe('1800px');
        });

        it('accepts "2000px"', function () {
            expect((new OptionsPage())->sanitize_max_width('2000px'))->toBe('2000px');
        });

        it('returns default "1800px" for invalid value', function () {
            expect((new OptionsPage())->sanitize_max_width('9999px'))->toBe('1800px');
        });
    });

    describe('sanitize_markdown_shortcodes()', function () {
        it('returns empty string for non-string input', function () {
            $page = new OptionsPage();
            expect($page->sanitize_markdown_shortcodes(null))->toBe('');
            expect($page->sanitize_markdown_shortcodes(123))->toBe('');
        });

        it('sanitizes each line with wp_kses_post', function () {
            Functions\when('wp_kses_post')->alias(
                static fn(string $text): string => $text
            );

            $input = "[signature] = **Christopher Penn**\n[cta] = [Book Now](/contact/)";
            $result = (new OptionsPage())->sanitize_markdown_shortcodes($input);

            expect($result)->toContain('[signature]');
            expect($result)->toContain('[cta]');
        });

        it('trims whitespace from each line', function () {
            Functions\when('wp_kses_post')->alias(
                static fn(string $text): string => $text
            );

            $input = "  [foo] = bar  \n  [baz] = qux  ";
            $result = (new OptionsPage())->sanitize_markdown_shortcodes($input);

            $lines = explode("\n", $result);
            expect($lines[0])->toBe('[foo] = bar');
            expect($lines[1])->toBe('[baz] = qux');
        });

        it('returns empty string for empty string input', function () {
            Functions\when('wp_kses_post')->alias(static fn(string $t): string => $t);

            $result = (new OptionsPage())->sanitize_markdown_shortcodes('');
            expect($result)->toBe('');
        });
    });

    // ------------------------------------------------------------------
    // Section description render methods (simple HTML output)
    // ------------------------------------------------------------------

    describe('render section descriptions', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('render_rss_section outputs RSS feed description', function () {
            ob_start();
            (new OptionsPage())->render_rss_section();
            $output = ob_get_clean();
            expect($output)->toContain('RSS');
        });

        it('render_archive_section outputs archive description', function () {
            ob_start();
            (new OptionsPage())->render_archive_section();
            $output = ob_get_clean();
            expect($output)->toContain('archive');
        });

        it('render_markdown_section outputs markdown description', function () {
            ob_start();
            (new OptionsPage())->render_markdown_section();
            $output = ob_get_clean();
            expect($output)->toContain('markdown');
        });

        it('render_layout_section outputs layout description', function () {
            ob_start();
            (new OptionsPage())->render_layout_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
        });

        it('render_homepage_section outputs homepage description', function () {
            ob_start();
            (new OptionsPage())->render_homepage_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
            expect($output)->toContain('no longer uses');
        });

        it('render_conversion_section outputs conversion description', function () {
            ob_start();
            (new OptionsPage())->render_conversion_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
        });

        it('render_social_proof_section outputs social proof description', function () {
            ob_start();
            (new OptionsPage())->render_social_proof_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
        });

        it('render_schema_section outputs schema description', function () {
            ob_start();
            (new OptionsPage())->render_schema_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
        });

        it('render_shortcodes_section outputs shortcodes description', function () {
            ob_start();
            (new OptionsPage())->render_shortcodes_section();
            $output = ob_get_clean();
            expect($output)->toContain('<p>');
        });
    });

    // ------------------------------------------------------------------
    // Field render methods
    // ------------------------------------------------------------------

    describe('render_rss_content_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed => 'full'
            );
        });

        it('renders radio buttons for each available RSS mode', function () {
            ob_start();
            (new OptionsPage())->render_rss_content_field();
            $output = ob_get_clean();

            expect($output)->toContain('type="radio"');
            expect($output)->toContain('value="full"');
            expect($output)->toContain('value="excerpt"');
        });
    });

    describe('render_archive_display_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders radio buttons for excerpt and full', function () {
            ob_start();
            (new OptionsPage())->render_archive_display_field();
            $output = ob_get_clean();

            expect($output)->toContain('type="radio"');
            expect($output)->toContain('value="excerpt"');
            expect($output)->toContain('value="full"');
        });
    });

    describe('render_parser_type_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders radio buttons for extra and basic', function () {
            ob_start();
            (new OptionsPage())->render_parser_type_field();
            $output = ob_get_clean();

            expect($output)->toContain('value="extra"');
            expect($output)->toContain('value="basic"');
        });
    });

    describe('render_sidebar_width_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a select element with all width options', function () {
            ob_start();
            (new OptionsPage())->render_sidebar_width_field();
            $output = ob_get_clean();

            expect($output)->toContain('<select');
            expect($output)->toContain('value="300px"');
            expect($output)->toContain('value="350px"');
            expect($output)->toContain('value="400px"');
        });
    });

    describe('render_max_width_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a select element with all max-width options', function () {
            ob_start();
            (new OptionsPage())->render_max_width_field();
            $output = ob_get_clean();

            expect($output)->toContain('<select');
            expect($output)->toContain('value="1400px"');
            expect($output)->toContain('value="1800px"');
        });
    });

    describe('render_hero_image_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders text input with media upload button', function () {
            ob_start();
            (new OptionsPage())->render_hero_image_field();
            $output = ob_get_clean();

            expect($output)->toContain('crispytheme_hero_image');
            expect($output)->toContain('crispytheme-media-upload');
        });

        it('shows image preview when a value is set', function () {
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed =>
                    'crispytheme_hero_image' === $key ? 'https://example.com/hero.jpg' : $default
            );

            ob_start();
            (new OptionsPage())->render_hero_image_field();
            $output = ob_get_clean();

            expect($output)->toContain('<img');
            expect($output)->toContain('hero.jpg');
        });
    });

    describe('render_hero_eyebrow_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for hero eyebrow', function () {
            ob_start();
            (new OptionsPage())->render_hero_eyebrow_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_hero_eyebrow"');
            expect($output)->toContain('type="text"');
        });
    });

    describe('render_hero_headline_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for hero headline', function () {
            ob_start();
            (new OptionsPage())->render_hero_headline_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_hero_headline"');
        });
    });

    describe('render_hero_subheadline_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a textarea for hero subheadline', function () {
            ob_start();
            (new OptionsPage())->render_hero_subheadline_field();
            $output = ob_get_clean();

            expect($output)->toContain('<textarea');
            expect($output)->toContain('crispytheme_hero_subheadline');
        });
    });

    describe('render_stat_events_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for events stat', function () {
            ob_start();
            (new OptionsPage())->render_stat_events_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_stat_events"');
        });
    });

    describe('render_stat_countries_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for countries stat', function () {
            ob_start();
            (new OptionsPage())->render_stat_countries_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_stat_countries"');
        });
    });

    describe('render_stat_satisfaction_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for satisfaction stat', function () {
            ob_start();
            (new OptionsPage())->render_stat_satisfaction_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_stat_satisfaction"');
        });
    });

    describe('render_video_enabled_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a checkbox for video showcase enabled', function () {
            ob_start();
            (new OptionsPage())->render_video_enabled_field();
            $output = ob_get_clean();

            expect($output)->toContain('type="checkbox"');
            expect($output)->toContain('crispytheme_video_showcase_enabled');
        });
    });

    describe('render_video_url_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a URL input for video URL', function () {
            ob_start();
            (new OptionsPage())->render_video_url_field();
            $output = ob_get_clean();

            expect($output)->toContain('type="url"');
            expect($output)->toContain('crispytheme_video_showcase_url');
        });
    });

    describe('render_video_heading_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input for video heading', function () {
            ob_start();
            (new OptionsPage())->render_video_heading_field();
            $output = ob_get_clean();

            expect($output)->toContain('name="crispytheme_video_showcase_heading"');
        });
    });

    describe('render_video_description_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a textarea for video description', function () {
            ob_start();
            (new OptionsPage())->render_video_description_field();
            $output = ob_get_clean();

            expect($output)->toContain('<textarea');
            expect($output)->toContain('crispytheme_video_showcase_description');
        });
    });

    describe('render_client_logo_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a media field for client logo', function () {
            ob_start();
            (new OptionsPage())->render_client_logo_field(['logo_number' => 1]);
            $output = ob_get_clean();

            expect($output)->toContain('crispytheme_client_logo_1');
            expect($output)->toContain('crispytheme-media-upload');
        });

        it('renders each of the 6 logos with correct names', function () {
            for ($i = 1; $i <= 6; $i++) {
                ob_start();
                (new OptionsPage())->render_client_logo_field(['logo_number' => $i]);
                $output = ob_get_clean();
                expect($output)->toContain("crispytheme_client_logo_{$i}");
            }
        });

        it('shows logo preview image when a URL is set', function () {
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed =>
                    'crispytheme_client_logo_2' === $key ? 'https://example.com/logo.png' : $default
            );

            ob_start();
            (new OptionsPage())->render_client_logo_field(['logo_number' => 2]);
            $output = ob_get_clean();

            expect($output)->toContain('<img');
        });

        it('uses empty string for unknown logo number (no client name lookup)', function () {
            ob_start();
            (new OptionsPage())->render_client_logo_field(['logo_number' => 99]);
            $output = ob_get_clean();

            expect($output)->toContain('crispytheme_client_logo_99');
        });
    });

    describe('render_shortcodes_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a textarea for shortcode definitions', function () {
            ob_start();
            (new OptionsPage())->render_shortcodes_field();
            $output = ob_get_clean();

            expect($output)->toContain('<textarea');
            expect($output)->toContain('crispytheme_markdown_shortcodes');
        });
    });

    // ------------------------------------------------------------------
    // Generic field renderers (render_text_input_field, render_url_input_field,
    // render_textarea_input_field, render_media_input_field)
    // ------------------------------------------------------------------

    describe('render_text_input_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a text input with option name, default, and description', function () {
            ob_start();
            (new OptionsPage())->render_text_input_field([
                'option'      => 'my_option',
                'default'     => 'My Default',
                'description' => 'This is a description.',
                'placeholder' => '',
                'class'       => 'large-text',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('type="text"');
            expect($output)->toContain('name="my_option"');
            expect($output)->toContain('This is a description.');
        });

        it('renders placeholder when provided', function () {
            ob_start();
            (new OptionsPage())->render_text_input_field([
                'option'      => 'my_option',
                'default'     => '',
                'description' => '',
                'placeholder' => 'Enter value here',
                'class'       => 'large-text',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('placeholder=');
        });

        it('omits placeholder attribute when placeholder is empty string', function () {
            ob_start();
            (new OptionsPage())->render_text_input_field([
                'option'      => 'my_option',
                'default'     => '',
                'description' => '',
                'placeholder' => '',
                'class'       => 'large-text',
            ]);
            $output = ob_get_clean();

            // Should not have a placeholder attribute at all when empty
            expect($output)->not->toContain('placeholder=');
        });
    });

    describe('render_url_input_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a url input type', function () {
            ob_start();
            (new OptionsPage())->render_url_input_field([
                'option'      => 'my_url_option',
                'default'     => '',
                'description' => '',
                'placeholder' => 'https://example.com',
                'class'       => 'large-text',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('type="url"');
            expect($output)->toContain('name="my_url_option"');
        });
    });

    describe('render_textarea_input_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a textarea with option name', function () {
            ob_start();
            (new OptionsPage())->render_textarea_input_field([
                'option'      => 'my_textarea',
                'default'     => 'Default text',
                'description' => 'Some description',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('<textarea');
            expect($output)->toContain('name="my_textarea"');
            expect($output)->toContain('Some description');
        });
    });

    describe('render_media_input_field()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders a media field with upload button', function () {
            ob_start();
            (new OptionsPage())->render_media_input_field([
                'option'      => 'my_media',
                'default'     => '',
                'description' => 'Choose an image',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('crispytheme-media-upload');
            expect($output)->toContain('data-target="my_media"');
            expect($output)->toContain('Choose an image');
        });

        it('shows preview image when value is set', function () {
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed =>
                    'my_media' === $key ? 'https://example.com/img.jpg' : $default
            );

            ob_start();
            (new OptionsPage())->render_media_input_field([
                'option'      => 'my_media',
                'default'     => '',
                'description' => '',
            ]);
            $output = ob_get_clean();

            expect($output)->toContain('<img');
            expect($output)->toContain('img.jpg');
        });
    });

    // ------------------------------------------------------------------
    // render_analytics_section() — depends on MarkdownDropdown static calls
    // Note: get_analytics_stats() uses get_option and get_top_posts uses $wpdb.
    // We mock get_option and bypass wpdb by returning empty array from get_top_posts.
    // ------------------------------------------------------------------

    describe('render_analytics_section()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('renders stats when no top posts data exists', function () {
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed => $default
            );

            // Mock $wpdb->get_results to return null (no posts).
            global $wpdb;
            $wpdb = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';

                public function prepare(string $query, mixed ...$args): string
                {
                    return $query;
                }

                public function get_results(string $query, string $output = 'OBJECT'): ?array
                {
                    return null;
                }
            };

            Functions\when('wp_date')->alias(
                static fn(string $format, int $timestamp): string => date($format, $timestamp)
            );

            ob_start();
            (new OptionsPage())->render_analytics_section();
            $output = ob_get_clean();

            // Should have the stats panel with 0 counts.
            expect($output)->toContain('Total Copies');
            expect($output)->toContain('Total Views');
            // Empty state message (no top posts).
            expect($output)->toContain('No usage data yet');
        });

        it('renders top posts table when data exists', function () {
            // Provide aggregate stats via option.
            // MarkdownDropdown::ANALYTICS_OPTION = 'crispytheme_markdown_analytics'
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed =>
                    'crispytheme_markdown_analytics' === $key
                        ? ['total_copies' => 42, 'total_views' => 100, 'last_updated' => '2026-01-01 00:00:00']
                        : $default
            );

            global $wpdb;
            $wpdb = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';

                public function prepare(string $query, mixed ...$args): string
                {
                    return $query;
                }

                public function get_results(string $query, string $output = 'OBJECT'): ?array
                {
                    return [
                        [
                            'ID'          => 1,
                            'post_title'  => 'Test Post',
                            'post_type'   => 'post',
                            'copy_count'  => '10',
                            'view_count'  => '20',
                            'total_count' => '30',
                        ],
                    ];
                }
            };

            Functions\when('get_edit_post_link')->justReturn('https://example.com/wp-admin/post.php?post=1&action=edit');
            Functions\when('wp_date')->alias(
                static fn(string $format, mixed $timestamp): string => '2026-01-01 00:00:00'
            );

            ob_start();
            (new OptionsPage())->render_analytics_section();
            $output = ob_get_clean();

            expect($output)->toContain('Top Posts by Markdown Usage');
            expect($output)->toContain('Test Post');
        });

        it('renders last updated timestamp when present', function () {
            // MarkdownDropdown::ANALYTICS_OPTION = 'crispytheme_markdown_analytics'
            Functions\when('get_option')->alias(
                static fn(string $key, mixed $default = false): mixed =>
                    'crispytheme_markdown_analytics' === $key
                        ? ['total_copies' => 0, 'total_views' => 0, 'last_updated' => '2026-01-14 12:00:00']
                        : $default
            );

            global $wpdb;
            $wpdb = new class {
                public string $posts = 'wp_posts';
                public string $postmeta = 'wp_postmeta';

                public function prepare(string $query, mixed ...$args): string
                {
                    return $query;
                }

                public function get_results(string $query, string $output = 'OBJECT'): ?array
                {
                    return null;
                }
            };

            Functions\when('wp_date')->alias(
                static fn(string $format, mixed $timestamp): string => 'January 14, 2026'
            );

            ob_start();
            (new OptionsPage())->render_analytics_section();
            $output = ob_get_clean();

            expect($output)->toContain('Last updated');
        });
    });

    // ------------------------------------------------------------------
    // render_options_page()
    // ------------------------------------------------------------------

    describe('render_options_page()', function () {
        beforeEach(function () {
            stubRenderFunctions();
        });

        it('outputs nothing when user lacks permission', function () {
            Functions\when('current_user_can')->justReturn(false);

            ob_start();
            (new OptionsPage())->render_options_page();
            $output = ob_get_clean();

            expect($output)->toBe('');
        });

        it('renders the options page wrap when user has permission', function () {
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('get_admin_page_title')->justReturn('CrispyTheme Options');
            Functions\when('settings_fields')->alias(static function (string $group): void {
                echo "<input type='hidden' name='option_page' value='{$group}'>";
            });
            Functions\when('do_settings_sections')->alias(static function (string $page): void {
                echo "<!-- settings sections -->";
            });
            Functions\when('submit_button')->alias(static function (): void {
                echo '<button type="submit">Save</button>';
            });

            ob_start();
            (new OptionsPage())->render_options_page();
            $output = ob_get_clean();

            expect($output)->toContain('<div class="wrap">');
            expect($output)->toContain('CrispyTheme Options');
        });
    });
});

describe('OptionsPage::sanitize_number', function () {
    beforeEach(function () {
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
    });

    it('keeps a blank field blank, so the default comes back and the page never shows $0', function () {
        expect(OptionsPage::sanitize_number(''))->toBe('');
        expect(OptionsPage::sanitize_number('  '))->toBe('');
    });

    it('keeps every digit of a number with a thousands comma', function () {
        expect(OptionsPage::sanitize_number('49,269'))->toBe('49269');
        expect(OptionsPage::sanitize_number('25000'))->toBe('25000');
    });

    it('saves a typed zero as zero', function () {
        expect(OptionsPage::sanitize_number('0'))->toBe('0');
    });

    it('drops a dollar sign, spaces, and the fraction, and never moves the decimal point', function () {
        expect(OptionsPage::sanitize_number('$25,000'))->toBe('25000');
        expect(OptionsPage::sanitize_number('25000.00'))->toBe('25000');
        expect(OptionsPage::sanitize_number('33.5'))->toBe('33');
        expect(OptionsPage::sanitize_number(' 30 '))->toBe('30');
    });

    it('treats text that is not a number as blank, so the default returns', function () {
        expect(OptionsPage::sanitize_number('$25k'))->toBe('');
        expect(OptionsPage::sanitize_number('abc'))->toBe('');
    });
});

describe('OptionsPage hero eyebrow', function () {
    it('has no default text, so the first save of Theme Options does not turn the eyebrow on', function () {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Admin/OptionsPage.php');

        expect($source)->not->toContain('Eight-time IBM Champion');
    });
});

describe('BookingFacts::number, the one number parser', function () {
    beforeEach(function () {
        Functions\when('absint')->alias(static fn($v) => abs((int) $v));
    });

    it('reads the saved value the way the options page saves it', function () {
        foreach (['$25,000' => '25000', '25,000.00' => '25000', '49,269' => '49269', '33.5' => '33', '' => '', 'abc' => ''] as $in => $out) {
            expect(\CrispyTheme\Booking\BookingFacts::number($in))->toBe($out);
            expect(OptionsPage::sanitize_number($in))->toBe($out);
        }
    });
});
