<?php
/**
 * Post Fixtures
 *
 * Provides preset post configurations for testing.
 *
 * @package CrispyTheme\Tests\Fixtures
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Fixtures;

/**
 * Post object fixtures for testing.
 */
class PostFixtures
{
    /**
     * Get default post attributes.
     *
     * @return array<string, mixed>
     */
    public static function defaultPost(): array
    {
        return [
            'ID'            => 1,
            'post_title'    => 'Test Post',
            'post_content'  => 'Test content',
            'post_excerpt'  => '',
            'post_status'   => 'publish',
            'post_type'     => 'post',
            'post_author'   => 1,
            'post_date'     => '2024-01-01 00:00:00',
            'post_modified' => '2024-01-01 00:00:00',
            'post_name'     => 'test-post',
            'post_parent'   => 0,
            'menu_order'    => 0,
            'guid'          => 'https://example.com/?p=1',
        ];
    }

    /**
     * Get published post attributes.
     *
     * @return array<string, mixed>
     */
    public static function publishedPost(): array
    {
        return array_merge(self::defaultPost(), [
            'post_status' => 'publish',
        ]);
    }

    /**
     * Get draft post attributes.
     *
     * @return array<string, mixed>
     */
    public static function draftPost(): array
    {
        return array_merge(self::defaultPost(), [
            'post_status' => 'draft',
            'post_title'  => 'Draft Post',
        ]);
    }

    /**
     * Get scheduled post attributes.
     *
     * @return array<string, mixed>
     */
    public static function scheduledPost(): array
    {
        $futureDate = date('Y-m-d H:i:s', strtotime('+1 week'));

        return array_merge(self::defaultPost(), [
            'post_status' => 'future',
            'post_date'   => $futureDate,
            'post_title'  => 'Scheduled Post',
        ]);
    }

    /**
     * Get page post attributes.
     *
     * @return array<string, mixed>
     */
    public static function page(): array
    {
        return array_merge(self::defaultPost(), [
            'post_type'  => 'page',
            'post_title' => 'Test Page',
        ]);
    }

    /**
     * Get post with markdown content.
     *
     * @param string $markdown Markdown content.
     * @return array<string, mixed>
     */
    public static function withMarkdownContent(string $markdown): array
    {
        return array_merge(self::defaultPost(), [
            'post_content'     => '', // Markdown is stored in meta.
            '_markdown_content' => $markdown,
        ]);
    }

    /**
     * Get post with specific ID.
     *
     * @param int $id Post ID.
     * @return array<string, mixed>
     */
    public static function withId(int $id): array
    {
        return array_merge(self::defaultPost(), [
            'ID'   => $id,
            'guid' => "https://example.com/?p={$id}",
        ]);
    }

    /**
     * Get post with specific title.
     *
     * @param string $title Post title.
     * @return array<string, mixed>
     */
    public static function withTitle(string $title): array
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? '');

        return array_merge(self::defaultPost(), [
            'post_title' => $title,
            'post_name'  => $slug,
        ]);
    }

    /**
     * Get post with specific content.
     *
     * @param string $content Post content.
     * @return array<string, mixed>
     */
    public static function withContent(string $content): array
    {
        return array_merge(self::defaultPost(), [
            'post_content' => $content,
        ]);
    }

    /**
     * Get post with excerpt.
     *
     * @param string $excerpt Post excerpt.
     * @return array<string, mixed>
     */
    public static function withExcerpt(string $excerpt): array
    {
        return array_merge(self::defaultPost(), [
            'post_excerpt' => $excerpt,
        ]);
    }

    /**
     * Get post for RSS feed testing.
     *
     * @return array<string, mixed>
     */
    public static function forRssFeed(): array
    {
        return array_merge(self::publishedPost(), [
            'post_title'   => 'RSS Feed Post',
            'post_content' => 'Content for RSS feed testing.',
            'post_excerpt' => 'Excerpt for RSS.',
        ]);
    }

    /**
     * Get multiple posts with sequential IDs.
     *
     * @param int $count Number of posts.
     * @return array<int, array<string, mixed>>
     */
    public static function multiple(int $count): array
    {
        $posts = [];

        for ($i = 1; $i <= $count; ++$i) {
            $posts[] = array_merge(self::defaultPost(), [
                'ID'         => $i,
                'post_title' => "Test Post {$i}",
                'post_name'  => "test-post-{$i}",
                'guid'       => "https://example.com/?p={$i}",
            ]);
        }

        return $posts;
    }

    /**
     * Get post for admin context.
     *
     * @return array<string, mixed>
     */
    public static function forAdmin(): array
    {
        return array_merge(self::defaultPost(), [
            'post_title'  => 'Admin Context Post',
            'post_status' => 'draft',
        ]);
    }

    /**
     * Get post for preview.
     *
     * @return array<string, mixed>
     */
    public static function forPreview(): array
    {
        return array_merge(self::draftPost(), [
            'post_title'       => 'Preview Post',
            '_markdown_content' => MarkdownFixtures::sample(),
        ]);
    }
}
