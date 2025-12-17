<?php
/**
 * Plugin Name: GraphQL Sitemap
 * Description: Expose a lightweight sitemap over WPGraphQL (posts/pages/CPTs and taxonomies with URIs).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('graphql_register_types', function () {
    if (!function_exists('register_graphql_object_type')) {
        return;
    }

    register_graphql_object_type('SitemapEntry', [
        'description' => 'Sitemap entry',
        'fields' => [
            'id' => ['type' => 'ID'],
            'databaseId' => ['type' => 'Int'],
            'uri' => ['type' => 'String'],
            'slug' => ['type' => 'String'],
            'type' => ['type' => 'String'],
            'modified' => ['type' => 'String'],
            'status' => ['type' => 'String'],
        ],
    ]);

    register_graphql_field('RootQuery', 'sitemap', [
        'type' => ['list_of' => 'SitemapEntry'],
        'description' => 'Lightweight sitemap entries (published only, uri must be present)',
        'args' => [
            'types' => [
                'type' => ['list_of' => 'String'],
                'description' => 'Post types to include (e.g., ["post","page","material"]). Defaults to public, non-builtin + post/page.',
            ],
            'limit' => [
                'type' => 'Int',
                'description' => 'Max results (default 500, max 2000)',
            ],
        ],
        'resolve' => function ($root, $args) {
            $limit = isset($args['limit']) ? intval($args['limit']) : 500;
            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 2000) {
                $limit = 2000;
            }

            $post_types = [];
            if (!empty($args['types'])) {
                $post_types = array_map('sanitize_key', $args['types']);
            } else {
                $post_types = get_post_types(['public' => true], 'names');
            }

            $entries = [];

            $posts = get_posts([
                'post_type' => $post_types,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            ]);

            foreach ($posts as $post_id) {
                $uri = function_exists('get_post_type_object') && function_exists('get_permalink') ? wp_make_link_relative(get_permalink($post_id)) : '';
                if (!$uri) {
                    continue;
                }
                $post = get_post($post_id);
                $entries[] = [
                    'id' => "post-{$post_id}",
                    'databaseId' => intval($post_id),
                    'uri' => $uri,
                    'slug' => $post->post_name,
                    'type' => $post->post_type,
                    'modified' => $post->post_modified_gmt,
                    'status' => $post->post_status,
                ];
                if (count($entries) >= $limit) {
                    break;
                }
            }

            // Include taxonomy terms with rewrite/uri.
            $taxonomies = get_taxonomies(['public' => true], 'objects');
            foreach ($taxonomies as $tax) {
                $terms = get_terms([
                    'taxonomy' => $tax->name,
                    'hide_empty' => false,
                    'number' => $limit,
                    'orderby' => 'term_id',
                    'order' => 'DESC',
                ]);
                if (is_wp_error($terms)) {
                    continue;
                }
                foreach ($terms as $term) {
                    $uri = get_term_link($term);
                    if (is_wp_error($uri)) {
                        continue;
                    }
                    $uri = wp_make_link_relative($uri);
                    if (!$uri) {
                        continue;
                    }
                    $entries[] = [
                        'id' => "term-{$term->term_id}",
                        'databaseId' => intval($term->term_id),
                        'uri' => $uri,
                        'slug' => $term->slug,
                        'type' => $tax->name,
                        'modified' => null,
                        'status' => 'publish',
                    ];
                    if (count($entries) >= $limit) {
                        break 2;
                    }
                }
            }

            return $entries;
        },
    ]);
});
