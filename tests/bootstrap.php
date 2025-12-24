<?php
/**
 * Test Bootstrap
 *
 * Sets up WordPress stubs and Brain Monkey for unit testing.
 *
 * @package CrispyTheme\Tests
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| WordPress Constants
|--------------------------------------------------------------------------
*/

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (!defined('CRISPY_THEME_VERSION')) {
    define('CRISPY_THEME_VERSION', '1.0.0-test');
}

if (!defined('CRISPY_THEME_DIR')) {
    define('CRISPY_THEME_DIR', dirname(__DIR__));
}

if (!defined('CRISPY_THEME_URI')) {
    define('CRISPY_THEME_URI', 'https://example.com/wp-content/themes/crispytheme');
}

/*
|--------------------------------------------------------------------------
| Composer Autoloader
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Brain Monkey
|--------------------------------------------------------------------------
*/

use Brain\Monkey;

/**
 * Set up Brain Monkey before each test.
 *
 * @return void
 */
function setUpBrainMonkey(): void
{
    Monkey\setUp();

    // Define minimal WordPress function stubs.
    // Most function mocking is now handled by the Concerns traits.
    Monkey\Functions\stubs([
        'wp_strip_all_tags' => static function (string $text): string {
            return strip_tags($text);
        },
        'wp_trim_words' => static function (string $text, int $num_words = 55, string $more = '...'): string {
            $words = explode(' ', $text);
            if (count($words) > $num_words) {
                return implode(' ', array_slice($words, 0, $num_words)) . $more;
            }
            return $text;
        },
        'esc_html' => static function (string $text): string {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        },
        'esc_attr' => static function (string $text): string {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        },
        '__' => static function (string $text, string $domain = 'default'): string {
            return $text;
        },
        'esc_html__' => static function (string $text, string $domain = 'default'): string {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        },
    ]);
}

/**
 * Tear down Brain Monkey after each test.
 *
 * @return void
 */
function tearDownBrainMonkey(): void
{
    Monkey\tearDown();
}
