<?php

namespace Nikogin\Framework\Abstracts;

use WP_Term;
use WP_Error;

abstract class TaxonomyRepository
{
    public function __construct(private string $taxonomy) {}

    public function getAll(array $args = []): array|WP_Error
    {
        return get_terms(array_merge([
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => false,
        ], $args));
    }

    public function getById(int $id): WP_Term|WP_Error|null
    {
        return get_term($id, $this->taxonomy);
    }

    public function getBySlug(string $slug): WP_Term|null
    {
        $term = get_term_by('slug', $slug, $this->taxonomy);
        return $term ?: null;
    }

    public function getByName(string $name): WP_Term|null
    {
        $term = get_term_by('name', $name, $this->taxonomy);
        return $term ?: null;
    }

    public function getChildren(int $parentId): array|WP_Error
    {
        return get_terms([
            'taxonomy'   => $this->taxonomy,
            'hide_empty' => false,
            'parent'     => $parentId,
        ]);
    }

    public function create(string $name, array $args = []): array|WP_Error
    {
        return wp_insert_term($name, $this->taxonomy, $args);
    }

    public function update(int $termId, array $args): array|WP_Error
    {
        return wp_update_term($termId, $this->taxonomy, $args);
    }

    public function delete(int $termId): bool|WP_Error
    {
        return wp_delete_term($termId, $this->taxonomy);
    }

    public function count(): int
    {
        $terms = get_terms(['taxonomy' => $this->taxonomy, 'hide_empty' => false, 'fields' => 'count']);
        return is_wp_error($terms) ? 0 : (int) $terms;
    }

    public function getForPost(int $postId, array $args = []): array|WP_Error
    {
        return wp_get_post_terms($postId, $this->taxonomy, $args);
    }

    public function attachToPost(int $postId, array $termIds): array|WP_Error
    {
        return wp_set_post_terms($postId, $termIds, $this->taxonomy, true);
    }

    public function syncToPost(int $postId, array $termIds): array|WP_Error
    {
        return wp_set_post_terms($postId, $termIds, $this->taxonomy, false);
    }

    public function detachFromPost(int $postId, array $termIds): bool|WP_Error
    {
        return wp_remove_object_terms($postId, $termIds, $this->taxonomy);
    }
}
