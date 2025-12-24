<?php
/**
 * Post Factory
 *
 * Factory for creating mock WP_Post objects.
 *
 * @package CrispyTheme\Tests\Factories
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Factories;

use CrispyTheme\Tests\Fixtures\PostFixtures;
use stdClass;

/**
 * Factory for creating WP_Post mock objects.
 */
class PostFactory
{
    /**
     * Post attributes.
     *
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Create a new factory instance.
     *
     * @param array<string, mixed> $attributes Initial attributes.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge(PostFixtures::defaultPost(), $attributes);
    }

    /**
     * Create a new factory instance (static).
     *
     * @param array<string, mixed> $attributes Initial attributes.
     * @return self
     */
    public static function new(array $attributes = []): self
    {
        return new self($attributes);
    }

    /**
     * Create a mock post object directly.
     *
     * @param array<string, mixed> $attributes Post attributes.
     * @return stdClass Mock post object.
     */
    public function create(array $attributes = []): stdClass
    {
        return (object) array_merge($this->attributes, $attributes);
    }

    /**
     * Set post ID.
     *
     * @param int $id Post ID.
     * @return self
     */
    public function withId(int $id): self
    {
        $this->attributes['ID']   = $id;
        $this->attributes['guid'] = "https://example.com/?p={$id}";
        return $this;
    }

    /**
     * Set post title.
     *
     * @param string $title Post title.
     * @return self
     */
    public function withTitle(string $title): self
    {
        $this->attributes['post_title'] = $title;
        $this->attributes['post_name']  = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? '');
        return $this;
    }

    /**
     * Set post content.
     *
     * @param string $content Post content.
     * @return self
     */
    public function withContent(string $content): self
    {
        $this->attributes['post_content'] = $content;
        return $this;
    }

    /**
     * Set markdown content (stored in meta).
     *
     * @param string $markdown Markdown content.
     * @return self
     */
    public function withMarkdown(string $markdown): self
    {
        $this->attributes['_markdown_content'] = $markdown;
        $this->attributes['post_content']      = ''; // Clear regular content.
        return $this;
    }

    /**
     * Set post excerpt.
     *
     * @param string $excerpt Post excerpt.
     * @return self
     */
    public function withExcerpt(string $excerpt): self
    {
        $this->attributes['post_excerpt'] = $excerpt;
        return $this;
    }

    /**
     * Set post status.
     *
     * @param string $status Post status.
     * @return self
     */
    public function withStatus(string $status): self
    {
        $this->attributes['post_status'] = $status;
        return $this;
    }

    /**
     * Set post type.
     *
     * @param string $type Post type.
     * @return self
     */
    public function withType(string $type): self
    {
        $this->attributes['post_type'] = $type;
        return $this;
    }

    /**
     * Set post author.
     *
     * @param int $authorId Author ID.
     * @return self
     */
    public function withAuthor(int $authorId): self
    {
        $this->attributes['post_author'] = $authorId;
        return $this;
    }

    /**
     * Set post date.
     *
     * @param string $date Date string (Y-m-d H:i:s format).
     * @return self
     */
    public function withDate(string $date): self
    {
        $this->attributes['post_date'] = $date;
        return $this;
    }

    /**
     * Set modified date.
     *
     * @param string $date Date string (Y-m-d H:i:s format).
     * @return self
     */
    public function withModifiedDate(string $date): self
    {
        $this->attributes['post_modified'] = $date;
        return $this;
    }

    /**
     * Set post slug.
     *
     * @param string $slug Post slug.
     * @return self
     */
    public function withSlug(string $slug): self
    {
        $this->attributes['post_name'] = $slug;
        return $this;
    }

    /**
     * Set post parent.
     *
     * @param int $parentId Parent post ID.
     * @return self
     */
    public function withParent(int $parentId): self
    {
        $this->attributes['post_parent'] = $parentId;
        return $this;
    }

    /**
     * Set as published.
     *
     * @return self
     */
    public function published(): self
    {
        return $this->withStatus('publish');
    }

    /**
     * Set as draft.
     *
     * @return self
     */
    public function draft(): self
    {
        return $this->withStatus('draft');
    }

    /**
     * Set as page.
     *
     * @return self
     */
    public function asPage(): self
    {
        return $this->withType('page');
    }

    /**
     * Set as post.
     *
     * @return self
     */
    public function asPost(): self
    {
        return $this->withType('post');
    }

    /**
     * Create multiple posts.
     *
     * @param int $count Number of posts to create.
     * @return array<stdClass> Array of mock post objects.
     */
    public function createMany(int $count): array
    {
        $posts = [];

        for ($i = 1; $i <= $count; ++$i) {
            $posts[] = (new self($this->attributes))
                ->withId($i)
                ->withTitle($this->attributes['post_title'] . " {$i}")
                ->create();
        }

        return $posts;
    }

    /**
     * Get current attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set a custom attribute.
     *
     * @param string $key   Attribute key.
     * @param mixed  $value Attribute value.
     * @return self
     */
    public function with(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Merge attributes.
     *
     * @param array<string, mixed> $attributes Attributes to merge.
     * @return self
     */
    public function merge(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }
}
