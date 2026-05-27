<?php

namespace Nikogin\Framework\Abstracts;

use WP_Post;
use WP_Query;

abstract class WpRepository
{
    protected string $postType;

    public function __construct(string $postType)
    {
        $this->postType = $postType;
    }

    /**
     * Insert a new post.
     */
    public function insert(array $data): int
    {
        $data['post_type'] = $this->postType;
        $data['post_status'] = $data['post_status'] ?? 'draft';

        return wp_insert_post($data);
    }

    /**
     * Update an existing post.
     */
    public function update(int $postId, array $data): bool|int
    {
        $data['ID'] = $postId;
        $data['post_type'] = $this->postType;

        return wp_update_post($data);
    }

    /**
     * Delete a post permanently.
     */
    public function delete(int $postId): bool
    {
        return (bool) wp_delete_post($postId, true);
    }

    /**
     * Get a post by ID.
     */
    public function getOne(int $postId): ?WP_Post
    {
        return get_post($postId) ?: null;
    }

    /**
     * Get all posts of this type.
     */
    public function getAll(array $args = []): array | WP_Query
    {
        $language = $args['language'] ?? null;
        $exclude = $args['exclude'] ?? [];

        unset($args['language'], $args['exclude']);

        if ($language) {
            do_action('wpml_switch_language', $language);
        }

        $defaults = [
            'post_type'   => $this->postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        if (!empty($exclude)) {
            $defaults['post__not_in'] = array_map('intval', (array)$exclude);
        }

        return new WP_Query(array_merge($defaults, $args));
    }

    /**
     * Find post by title.
     */
    public function findByTitle(string $title): ?WP_Post
    {
        $posts = get_posts([
            'post_type'   => $this->postType,
            'title'       => $title,
            'numberposts' => 1,
        ]);

        return $posts[0] ?? null;
    }

    /**
     * Find post by slug.
     */
    public function findBySlug(string $slug): ?WP_Post
    {
        $posts = get_posts([
            'post_type'   => $this->postType,
            'name'        => $slug,
            'numberposts' => 1,
        ]);

        return $posts[0] ?? null;
    }

    /**
     * Find posts by multiple meta key/value pairs.
     *
     * @param array $metaFields Array of ['key' => ..., 'value' => ..., 'compare' => ..., 'type' => ...] entries or simplified ['key' => value] form.
     * @param int $limit Number of posts to return (-1 for all).
     * @return WP_Post[] Matching posts.
     */
    public function findByMetaFields(array $metaFields, int $limit = -1): array
    {
        $metaQuery = [];

        foreach ($metaFields as $key => $value) {
            if (is_array($value) && isset($value['key'], $value['value'])) {
                $metaQuery[] = $value; // full meta_query format
            } else {
                $metaQuery[] = [
                    'key'     => $key,
                    'value'   => $value,
                    'compare' => '=',
                ];
            }
        }

        return get_posts([
            'post_type'   => $this->postType,
            'post_status' => 'any',
            'meta_query'  => $metaQuery,
            'numberposts' => $limit,
        ]);
    }

    public function filter(array $filters = []): WP_Query
    {
        $language = $filters['language'] ?? null;
        $exclude = $filters['exclude'] ?? [];

        if ($language) {
            do_action('wpml_switch_language', $language);
        }

        $queryArgs = [
            'post_type'      => $this->postType,
            'post_status'    => 'publish',
            'posts_per_page' => $filters['per_page'] ?? -1,
            'paged'          => $filters['page'] ?? 1,
        ];

        if (!empty($filters['meta'])) {
            $queryArgs['meta_query'] = $filters['meta'];
        }

        if (!empty($filters['tax'])) {
            $queryArgs['tax_query'] = $filters['tax'];
        }

        if (!empty($filters['search'])) {
            $queryArgs['s'] = $filters['search'];
        }

        if (!empty($filters['id'])) {
            $queryArgs['p'] = (int)$filters['id'];
        }

        if (!empty($exclude)) {
            $queryArgs['post__not_in'] = array_map('intval', (array)$exclude);
        }


        return new WP_Query($queryArgs);
    }
}
