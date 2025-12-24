<?php
/**
 * Mocks WordPress Functions Trait
 *
 * Provides common WordPress function mocking for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

use Brain\Monkey\Functions;

/**
 * Trait for WordPress function mocking.
 */
trait MocksWordPressFunctions
{
    /**
     * Post meta storage for testing.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $postMeta = [];

    /**
     * Mock escape functions (esc_html, esc_attr, etc.).
     *
     * @return void
     */
    protected function mockEscapeFunctions(): void
    {
        Functions\when('esc_html')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        Functions\when('esc_attr')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        Functions\when('esc_url')->alias(
            static fn(string $url): string => filter_var($url, FILTER_SANITIZE_URL) ?: ''
        );

        Functions\when('esc_textarea')->alias(
            static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        Functions\when('esc_js')->alias(
            static fn(string $text): string => addslashes($text)
        );

        Functions\when('wp_kses_post')->alias(
            static fn(string $text): string => $text
        );
    }

    /**
     * Mock translation functions (__, _e, esc_html__, etc.).
     *
     * @return void
     */
    protected function mockTranslationFunctions(): void
    {
        Functions\when('__')->alias(
            static fn(string $text, string $domain = 'default'): string => $text
        );

        Functions\when('_e')->alias(
            static function (string $text, string $domain = 'default'): void {
                echo $text;
            }
        );

        Functions\when('esc_html__')->alias(
            static fn(string $text, string $domain = 'default'): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        Functions\when('esc_attr__')->alias(
            static fn(string $text, string $domain = 'default'): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );

        Functions\when('_x')->alias(
            static fn(string $text, string $context, string $domain = 'default'): string => $text
        );

        Functions\when('_n')->alias(
            static fn(string $single, string $plural, int $number, string $domain = 'default'): string => $number === 1 ? $single : $plural
        );
    }

    /**
     * Mock sanitization functions.
     *
     * @return void
     */
    protected function mockSanitizationFunctions(): void
    {
        Functions\when('sanitize_text_field')->alias(
            static fn(string $text): string => strip_tags($text)
        );

        Functions\when('sanitize_title')->alias(
            static fn(string $title): string => strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $title) ?? $title)
        );

        Functions\when('wp_strip_all_tags')->alias(
            static fn(string $text): string => strip_tags($text)
        );

        Functions\when('wp_trim_words')->alias(
            static function (string $text, int $num_words = 55, string $more = '...'): string {
                $words = explode(' ', $text);
                if (count($words) > $num_words) {
                    return implode(' ', array_slice($words, 0, $num_words)) . $more;
                }
                return $text;
            }
        );
    }

    /**
     * Mock post meta functions.
     *
     * @return void
     */
    protected function mockPostMetaFunctions(): void
    {
        Functions\when('get_post_meta')->alias(
            function (int $post_id, string $key = '', bool $single = false): mixed {
                if (!isset($this->postMeta[$post_id])) {
                    return $single ? '' : [];
                }

                if ($key === '') {
                    return $this->postMeta[$post_id];
                }

                if (!isset($this->postMeta[$post_id][$key])) {
                    return $single ? '' : [];
                }

                return $single ? $this->postMeta[$post_id][$key] : [$this->postMeta[$post_id][$key]];
            }
        );

        Functions\when('update_post_meta')->alias(
            function (int $post_id, string $key, mixed $value): bool {
                if (!isset($this->postMeta[$post_id])) {
                    $this->postMeta[$post_id] = [];
                }
                $this->postMeta[$post_id][$key] = $value;
                return true;
            }
        );

        Functions\when('delete_post_meta')->alias(
            function (int $post_id, string $key): bool {
                if (isset($this->postMeta[$post_id][$key])) {
                    unset($this->postMeta[$post_id][$key]);
                    return true;
                }
                return false;
            }
        );

        Functions\when('add_post_meta')->alias(
            function (int $post_id, string $key, mixed $value, bool $unique = false): int|bool {
                if ($unique && isset($this->postMeta[$post_id][$key])) {
                    return false;
                }
                if (!isset($this->postMeta[$post_id])) {
                    $this->postMeta[$post_id] = [];
                }
                $this->postMeta[$post_id][$key] = $value;
                return 1;
            }
        );
    }

    /**
     * Set post meta directly for testing.
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key.
     * @param mixed  $value   Meta value.
     * @return void
     */
    protected function setPostMeta(int $post_id, string $key, mixed $value): void
    {
        if (!isset($this->postMeta[$post_id])) {
            $this->postMeta[$post_id] = [];
        }
        $this->postMeta[$post_id][$key] = $value;
    }

    /**
     * Get post meta directly for testing.
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key.
     * @return mixed Meta value or null.
     */
    protected function getPostMetaDirect(int $post_id, string $key): mixed
    {
        return $this->postMeta[$post_id][$key] ?? null;
    }

    /**
     * Clear all post meta.
     *
     * @return void
     */
    protected function clearPostMeta(): void
    {
        $this->postMeta = [];
    }

    /**
     * Mock common utility functions.
     *
     * @return void
     */
    protected function mockUtilityFunctions(): void
    {
        Functions\when('get_the_ID')->justReturn(1);

        Functions\when('is_singular')->justReturn(true);

        Functions\when('is_admin')->justReturn(false);

        Functions\when('current_user_can')->justReturn(true);

        Functions\when('wp_nonce_field')->alias(
            static function (string $action, string $name = '_wpnonce'): string {
                return "<input type=\"hidden\" name=\"{$name}\" value=\"test_nonce\" />";
            }
        );

        Functions\when('wp_verify_nonce')->justReturn(true);

        Functions\when('check_admin_referer')->justReturn(true);
    }

    /**
     * Mock is_singular with a specific value.
     *
     * @param bool|string|array<string> $value Return value or post type(s).
     * @return void
     */
    protected function mockIsSingular(bool|string|array $value = true): void
    {
        if (is_bool($value)) {
            Functions\when('is_singular')->justReturn($value);
        } else {
            Functions\when('is_singular')->alias(
                static fn(string|array $post_types = ''): bool => in_array($post_types, (array) $value, true)
            );
        }
    }

    /**
     * Mock get_the_ID with a specific value.
     *
     * @param int $post_id Post ID to return.
     * @return void
     */
    protected function mockGetTheId(int $post_id): void
    {
        Functions\when('get_the_ID')->justReturn($post_id);
    }

    /**
     * Mock is_admin with a specific value.
     *
     * @param bool $value Return value.
     * @return void
     */
    protected function mockIsAdmin(bool $value = true): void
    {
        Functions\when('is_admin')->justReturn($value);
    }

    /**
     * Set up all common WordPress function mocks.
     *
     * @return void
     */
    protected function setupWordPressFunctions(): void
    {
        $this->mockEscapeFunctions();
        $this->mockTranslationFunctions();
        $this->mockSanitizationFunctions();
        $this->mockPostMetaFunctions();
        $this->mockUtilityFunctions();
    }
}
