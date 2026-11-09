<?php
/**
 * Test helper: make the newsletter count functions answer in every test, whether or not another test file loaded
 * inc/template-tags.php. Brain Monkey keeps a stub defined after a test used it, and an unmocked stub throws.
 *
 * @package CrispyTheme\Tests
 */

declare(strict_types=1);

use Brain\Monkey\Functions;

/**
 * Stub the subscriber functions, or let the real ones run on a transient that is empty.
 *
 * @return void
 */
function crispySubscriberSetup(): void
{
    $real = function_exists('crispytheme_get_subscriber_count')
        && str_ends_with((string) (new ReflectionFunction('crispytheme_get_subscriber_count'))->getFileName(), 'template-tags.php');

    if ($real) {
        Functions\when('get_transient')->justReturn(false);

        return;
    }

    Functions\when('crispytheme_get_subscriber_count')->justReturn('About 292K');
    Functions\when('crispytheme_get_subscriber_as_of')->justReturn('October 2026');
}
